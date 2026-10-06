<?php

namespace App\Services;

use App\Models\Refund;
use Razorpay\Api\Api;

class ItemRefundGateway
{
    public function refund(Refund $refund): array
    {
        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        return $api->payment->fetch($refund->razorpay_payment_id)->refund([
            'amount' => (int) round((float) $refund->refund_amount * 100),
            'receipt' => 'item-refund-' . $refund->id,
            'notes' => ['order_id' => $refund->order_id, 'refund_record_id' => $refund->id, 'reason' => 'Admin product-wise refund'],
        ])->toArray();
    }
}
