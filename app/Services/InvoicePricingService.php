<?php

namespace App\Services;

use App\Models\Order;

class InvoicePricingService
{
    /**
     * Resolve the amounts that must be displayed on an order invoice.
     *
     * Completed orders keep GST and platform-fee snapshots because settings can
     * change after checkout. Legacy orders without snapshots fall back to the
     * current settings supplied by the caller.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, gstAmount: float, platformFee: float}
     */
    public function resolve(
        Order $order,
        array $items,
        float $gstPercent,
        float $fallbackPlatformFee = 0
    ): array {
        $subtotal = collect($items)->sum(function (array $item): float {
            if (array_key_exists('total', $item)) {
                return (float) $item['total'];
            }

            return (float) ($item['price'] ?? 0) * (float) ($item['quantity'] ?? 1);
        });

        $storedGst = $order->getAttribute('calculated_gst');
        $storedPlatformFee = $order->getAttribute('platform_fee');

        return [
            'subtotal' => round($subtotal, 2),
            'gstAmount' => round(
                $storedGst !== null ? (float) $storedGst : ($subtotal * $gstPercent) / 100,
                2
            ),
            'platformFee' => round(
                $storedPlatformFee !== null ? (float) $storedPlatformFee : $fallbackPlatformFee,
                2
            ),
        ];
    }
}