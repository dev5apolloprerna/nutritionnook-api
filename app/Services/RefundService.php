<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\DB;

class RefundService
{
    /**
     * Process full or partial refund for a rejected order.
     * Reads refund_percentage from the order itself (set before calling this).
     */
    public function refundOrder(Order $order): Refund
    {
        if ($order->status !== 'rejected' || $order->status !== 'delivered') {
            throw new \Exception('Only rejected orders can be refunded.');
        }

        if ($order->payment_status !== 'received') {
            throw new \Exception('Payment not captured yet.');
        }

        if ($order->is_refunded) {
            throw new \Exception('Order already refunded.');
        }

        if (empty($order->razorpay_payment_id)) {
            throw new \Exception('Razorpay payment ID missing.');
        }

        $baseAmount = $order->amount - ($order->discount_amount ?? 0);

        if ($baseAmount <= 0) {
            throw new \Exception('Invalid refund base amount.');
        }

        // Use refund_percentage stored on the order (0, 50, or 100)
        $refundPercentage = (int) ($order->refund_percentage ?? 100);

        if ($refundPercentage <= 0) {
            throw new \Exception('No refund applicable for this order.');
        }

        $refundAmount = round(($baseAmount * $refundPercentage) / 100, 2);

        if ($refundAmount <= 0) {
            throw new \Exception('Calculated refund amount is zero.');
        }

        return DB::transaction(function () use ($order, $refundAmount, $refundPercentage) {

            $refund = Refund::create([
                'order_id'            => $order->id,
                'user_id'             => $order->user_id,
                'refund_amount'       => $refundAmount,
                'refund_percentage'   => $refundPercentage,
                'razorpay_payment_id' => $order->razorpay_payment_id,
                'status'              => 'pending',
            ]);

            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            try {
                $razorpayRefund = $api->payment
                    ->fetch($order->razorpay_payment_id)
                    ->refund([
                        'amount' => (int) ($refundAmount * 100), // INR → paise
                        'notes'  => [
                            'order_id'          => $order->id,
                            'reason'            => 'Order rejected by admin',
                            'refund_percentage' => $refundPercentage,
                        ],
                    ]);

                $refund->update([
                    'razorpay_refund_id' => $razorpayRefund->id,
                    'status'             => 'processed',
                    'gateway_response'   => json_encode($razorpayRefund->toArray()),
                ]);

                $order->update([
                    'is_refunded' => true,
                    'refunded_at' => now(),
                ]);

                return $refund;

            } catch (\Exception $e) {
                $refund->update([
                    'status' => 'failed',
                    'reason' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }
}