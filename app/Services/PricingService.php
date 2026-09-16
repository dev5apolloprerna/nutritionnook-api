<?php

namespace App\Services;

use App\Models\FoodDish;
use App\Models\User;
use App\DTOs\PricingDTO;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function __construct(
        private CouponService $couponService
    ) {}

    public function calculate(array $items, ?int $couponId, User $user): PricingDTO
    {
        $foodIds = collect($items)->pluck('id');
        
        $foods = FoodDish::with('chef')
            ->whereIn('id', $foodIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $subtotal = 0;
        $normalizedItems = [];

        foreach ($items as $item) {

            /** @var FoodDish|null $food */
            $food = $foods->get($item['id']);

            // 1️⃣ Food exists & stock check
            if (!$food || !$food->in_stock) {
                throw ValidationException::withMessages([
                    'items' => "Food item {$item['id']} is out of stock"
                ]);
            }

            // 2️⃣ Chef must exist
            if (!$food->chef) {
                throw ValidationException::withMessages([
                    'chef' => "Chef not found for food {$food->name}"
                ]);
            }

            // 3️⃣ Chef availability check
            if (!$food->chef->available) {
                throw ValidationException::withMessages([
                    'chef' => "Chef unavailable for food {$food->name}"
                ]);
            }

            // 4️⃣ Price calculation
            $lineTotal = $food->price * $item['quantity'];
            $subtotal += $lineTotal;

            $normalizedItems[] = [
                'id'       => $food->id,
                'name'     => $food->name,
                'price'    => $food->price,
                'quantity' => $item['quantity'],
                'total'    => $lineTotal
            ];
        }

        $gstPercent = (float) \DB::table('settings')->first()->gst ?? 0;
        $gstAmount  = ($subtotal * $gstPercent) / 100;

        $discountData = $this->couponService
            ->calculate($couponId, $subtotal, $user);

        return new PricingDTO(
            subtotal: $subtotal,
            gst: $gstAmount,
            discount: $discountData['discount'],
            finalAmount: ($subtotal - $discountData['discount']) + $gstAmount,
            couponId: $discountData['coupon_id'],
            items: $normalizedItems
        );
    }
}
