<?php

namespace App\DTOs;

class PricingDTO
{
    public function __construct(
        public float $subtotal,
        public float $gst,
        public float $discount,
        public float $finalAmount,
        public ?int $couponId,
        public array $items
    ) {}
}
