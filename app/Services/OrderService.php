<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Jobs\CreateRazorpayOrderJob;
use App\Jobs\SendChefNotificationJob;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private PricingService $pricingService,
        private OrderRepository $orderRepository
    ) {}

    public function create(array $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user) {

            $pricing = $this->pricingService->calculate(
                $data['items'],
                $data['coupon_id'] ?? null,
                $user
            );

            $order = $this->orderRepository->create([
                'chef_id' => $data['chef_id'],
                'user_id' => $user->id,
                'items' => $pricing->items,
                'amount' => $pricing->finalAmount,
                'discount_amount' => $pricing->discount,
                'coupon_id' => $pricing->couponId,
                'status' => 'new',
                'payment_status' => 'pending',
                'date' => $data['date'] ?? now(),
            ]);

            // 🔥 CRITICAL: async after commit
            CreateRazorpayOrderJob::dispatch($order->id);
            SendChefNotificationJob::dispatch($order->id);

            return $order;
        });
    }
}
