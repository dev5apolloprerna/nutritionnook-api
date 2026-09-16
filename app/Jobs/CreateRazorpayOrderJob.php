<?php

namespace App\Jobs;

use App\Models\Order;
use Razorpay\Api\Api;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateRazorpayOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(): void
    {
        $order = Order::find($this->orderId);

        if (!$order) {
            return;
        }

        $api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );

        $razorOrder = $api->order->create([
            'receipt'  => 'order_' . $order->id,
            'amount'   => $order->amount * 100,
            'currency' => 'INR',
        ]);

        $order->update([
            'razorpay_order_id' => $razorOrder['id'],
        ]);
    }
}
