<?php

namespace App\Services;

class DishPriceCalculator
{
    public const DEFAULT_COMMISSION_PERCENTAGE = 10.0;

    /**
     * @return array{base_price: float, selling_price: float, commission_percentage: float}
     */
    public static function calculate(float $basePrice, ?float $commissionPercentage): array
    {
        $commissionPercentage = $commissionPercentage ?: self::DEFAULT_COMMISSION_PERCENTAGE;

        return [
            'base_price' => $basePrice,
            'selling_price' => round($basePrice + (($basePrice * $commissionPercentage) / 100)),
            'commission_percentage' => $commissionPercentage,
        ];
    }
}