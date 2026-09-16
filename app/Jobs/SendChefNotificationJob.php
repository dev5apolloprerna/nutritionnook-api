<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendChefNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $orderId;

    /**
     * Only pass primitive data (BEST PRACTICE)
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Laravel will auto-inject FCMService via Service Container
     */
    public function handle(FCMService $fcmService): void
    {
        $order = Order::select('id', 'chef_id')
            ->find($this->orderId);

        if (!$order || !$order->chef_id) {
            return; // silently fail (queue-safe)
        }

        $fcmService->sendNotificationToUser(
            $order->chef_id,
            'New Order 🍽️',
            'You received a new order',
            [
                'order_id' => (string) $order->id,
            ]
        );
    }
}
