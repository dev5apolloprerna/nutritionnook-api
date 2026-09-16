<?php
// app/Mail/OrderCreatedMail.php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use App\Models\Chef;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;
    public $chef;

    public function __construct(Order $order, User $user, Chef $chef)
    {
        $this->order = $order;
        $this->user = $user;
        $this->chef = $chef;
    }

    public function build()
    {
        return $this->subject('New Order Created - #' . $this->order->id)
                    ->view('emails.order-created')
                    ->with([
                        'order' => $this->order,
                        'user' => $this->user,
                        'chef' => $this->chef,
                        'orderItems' => json_decode($this->order->items, true)
                    ]);
    }
}