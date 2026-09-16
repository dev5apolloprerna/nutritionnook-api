<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function calculate(?int $couponId, float $subtotal, User $user): array
    {
        if (!$couponId) {
            return ['discount' => 0, 'coupon_id' => null];
        }

        $coupon = Coupon::where('id', $couponId)
            ->where('is_active', 1)
            ->first();

        if (!$coupon) {
            throw ValidationException::withMessages([
                'coupon' => 'Invalid coupon'
            ]);
        }

        if ($subtotal < $coupon->min_order_value) {
            throw ValidationException::withMessages([
                'coupon' => 'Minimum order not satisfied'
            ]);
        }

        $discount = $coupon->type === 'percentage'
            ? ($subtotal * $coupon->value) / 100
            : $coupon->value;

        if ($coupon->max_discount_amount) {
            $discount = min($discount, $coupon->max_discount_amount);
        }

        return [
            'discount' => min($discount, $subtotal),
            'coupon_id' => $coupon->id
        ];
    }
}
