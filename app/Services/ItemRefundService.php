<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Razorpay\Api\Errors\BadRequestError;

class ItemRefundService
{
    public function __construct(private ItemRefundGateway $gateway) {}

    public function lines(Order $order): array
    {
        $items = is_array($order->items) ? $order->items : json_decode($order->items ?? '[]', true);
        if (!is_array($items) || !$items) {
            $this->invalid('This order has no refundable item snapshots.');
        }
        $lines = [];
        foreach (array_values($items) as $index => $item) {
            $quantity = $item['quantity'] ?? 0;
            if (!is_numeric($quantity) || (int) $quantity != $quantity || $quantity < 1 || !isset($item['price']) || !is_numeric($item['price']) || $item['price'] < 0) {
                $this->invalid('Stored item prices or quantities are invalid.');
            }
            $lines[] = [
                'index' => $index,
                'id' => $item['id'] ?? null,
                'name' => $item['name'] ?? 'Item ' . ($index + 1),
                'quantity' => (int) $quantity,
                'gross_paise' => (int) round((float) $item['price'] * 100) * (int) $quantity,
            ];
        }
        $subtotal = array_sum(array_column($lines, 'gross_paise'));
        $discount = max(0, (int) round((float) $order->discount_amount * 100));
        $gst = max(0, (int) round((float) $order->calculated_gst * 100));
        $paid = (int) round((float) $order->amount * 100);
        if ($subtotal <= 0 || $discount > $subtotal || $paid <= 0) {
            $this->invalid('The stored order totals cannot be refunded.');
        }
        // Allocate the net item value in paise, assigning rounding remainders
        // deterministically so refunding separate lines never exceeds the total.
        $budget = min($paid, $subtotal - $discount + $gst);
        $allocated = 0;
        $remainders = [];
        foreach ($lines as $i => &$line) {
            $line['amount_paise'] = intdiv($budget * $line['gross_paise'], $subtotal);
            $allocated += $line['amount_paise'];
            $remainders[$i] = ($budget * $line['gross_paise']) % $subtotal;
        }
        unset($line);
        arsort($remainders);
        foreach (array_keys($remainders) as $i) {
            if ($allocated >= $budget) break;
            $lines[$i]['amount_paise']++;
            $allocated++;
        }
        $reserved = [];
        $pending = [];
        $wholeOrderReserved = (bool) $order->is_refunded;
        foreach ($order->refunds()->where('status', '!=', 'failed')->get() as $refund) {
            if ($refund->refund_type !== 'items') {
                $wholeOrderReserved = true;
            }
            foreach ($refund->selected_items ?? [] as $item) {
                $index = $item['index'];
                $reserved[$index] = ($reserved[$index] ?? 0) + ($item['amount_paise'] ?? ($lines[$index]['amount_paise'] ?? 0));
                if ($refund->status === 'pending') {
                    $pending[$index] = true;
                }
            }
        }
        foreach ($lines as &$line) {
            $line['remaining_paise'] = max(0, $line['amount_paise'] - ($reserved[$line['index']] ?? 0));
            $line['available'] = !$wholeOrderReserved && !isset($pending[$line['index']]) && $line['remaining_paise'] > 0;
        }
        return $lines;
    }

    public function refund(Order $order, array $indexes, ?array $amounts = null): Refund
    {
        $refund = DB::transaction(function () use ($order, $indexes, $amounts) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (!in_array($order->status, ['delivered', 'rejected'], true) || $order->payment_status !== 'received' || !$order->razorpay_payment_id) {
                $this->invalid('Only paid delivered or rejected orders can be refunded.');
            }
            if (!$indexes || count($indexes) !== count(array_unique($indexes))) {
                $this->invalid('Select at least one item, without duplicates.');
            }
            if ($amounts !== null && (count($amounts) !== count($indexes) || array_diff(array_keys($amounts), $indexes))) {
                $this->invalid('Supply an amount for each selected product only.');
            }
            $lines = $this->lines($order);
            $selected = [];
            foreach ($indexes as $index) {
                if (!is_int($index) || !isset($lines[$index]) || !$lines[$index]['available']) {
                    $this->invalid('A selected item is invalid, already refunded, or has a refund pending.');
                }
                $line = $lines[$index];
                $paise = $line['remaining_paise'];
                if ($amounts !== null) {
                    $value = (string) ($amounts[$index] ?? '');
                    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $value)) {
                        $this->invalid('Enter a positive refund amount with at most two decimal places.');
                    }
                    $parts = explode('.', $value);
                    $paise = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
                }
                if ($paise <= 0 || $paise > $line['remaining_paise']) {
                    $this->invalid('The refund amount must be positive and cannot exceed the product’s remaining refundable amount.');
                }
                $line['amount_paise'] = $paise;
                $selected[] = $line;
            }
            $amount = array_sum(array_column($selected, 'amount_paise'));
            $reserved = (int) round((float) $order->refunds()->where('status', '!=', 'failed')->sum('refund_amount') * 100);
            if ($reserved + $amount > (int) round((float) $order->amount * 100)) {
                $this->invalid('The refund exceeds the remaining paid amount.');
            }
            return Refund::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'refund_amount' => $amount / 100,
                'razorpay_payment_id' => $order->razorpay_payment_id,
                'refund_type' => 'items',
                'selected_items' => $selected,
                'status' => 'pending',
            ]);
        });

        // Commit the reservation before contacting the gateway. A timeout or
        // local write failure must not erase evidence of a potentially sent refund.
        try {
            $response = $this->gateway->refund($refund);
            if (empty($response['id']) || !in_array($response['status'] ?? '', ['pending', 'processed', 'failed'], true)) {
                throw new \RuntimeException('Unexpected refund gateway response.');
            }
            $refund = DB::transaction(function () use ($refund, $response) {
                $current = Refund::whereKey($refund->id)->lockForUpdate()->firstOrFail();
                // A webhook may have confirmed settlement before this response.
                if ($current->status === 'pending') {
                    $current->update([
                        'razorpay_refund_id' => $response['id'],
                        'status' => $response['status'],
                        'gateway_response' => $response,
                    ]);
                }
                return $current;
            });
        } catch (BadRequestError $e) {
            $refund->update(['status' => 'failed', 'reason' => 'Gateway rejected the refund request.']);
            throw new \RuntimeException('The payment gateway rejected the refund request.', 0, $e);
        } catch (\Throwable $e) {
            report($e);
            throw new \RuntimeException('Refund confirmation is pending. Do not retry these items until the gateway result is reconciled.', 0, $e);
        }
        if ($refund->status === 'failed') {
            throw new \RuntimeException('The payment gateway reported that the refund failed.');
        }
        return $refund;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['item_indexes' => $message]);
    }
}
