<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScheduledOrderPushService
{
    public function send(): void
    {
        $today = Carbon::today();

        Log::info('[ScheduledPush] Job started', [
            'date' => $today->toDateString()
        ]);

        $orders = Order::query()
            ->whereDate('order_date', '>=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('last_push_sent_at')
                  ->orWhereDate('last_push_sent_at', '<', $today);
            })
            ->get();

        Log::info('[ScheduledPush] Orders found', [
            'count' => $orders->count()
        ]);

        foreach ($orders as $order) {

            $daysLeft = $today->diffInDays($order->order_date, false);

            Log::info('[ScheduledPush] Processing order', [
                'order_id' => $order->id,
                'order_date' => $order->order_date,
                'days_left' => $daysLeft
            ]);

            if ($daysLeft < 0) {
                Log::info('[ScheduledPush] Skipped past order', [
                    'order_id' => $order->id
                ]);
                continue;
            }

            [$title, $body, $type] = $this->buildMessage($daysLeft, $order);

            try {
                (new FCMService())->sendNotificationToUser(
                    $order->chef_id,
                    $title,
                    $body,
                    [
                        'type'        => $type,
                        'orderId'     => (string) $order->id,
                        'days_left'   => (string) $daysLeft,
                        'order_date' => $order->order_date->toDateString(),
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]
                );

                $order->update([
                    'last_push_sent_at' => $today
                ]);

                Log::info('[ScheduledPush] Push sent successfully', [
                    'order_id' => $order->id
                ]);

            } catch (\Throwable $e) {
                Log::error('[ScheduledPush] Push failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('[ScheduledPush] Job completed');
    }

    private function buildMessage(int $daysLeft, Order $order): array
    {
        if ($daysLeft === 0) {
            return [
                'Today’s Order 🚨',
                "Order #{$order->id} is scheduled for today. Please prepare.",
                'order_today'
            ];
        }

        if ($daysLeft === 1) {
            return [
                'Order Tomorrow ⏰',
                "Order #{$order->id} is scheduled for tomorrow.",
                'order_tomorrow'
            ];
        }

        return [
            'Upcoming Order 📅',
            "Order #{$order->id} is scheduled in {$daysLeft} days.",
            'order_upcoming'
        ];
    }
}
