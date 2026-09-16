<?php
// app/Mail/OrderReminderMail.php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;

    public function __construct(Order $order, User $user)
    {
        $this->order = $order;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Reminder: Your Order #' . $this->order->id . ' is ' . ucfirst($this->order->status))
                    ->view('emails.order-reminder')
                    ->with([
                        'order' => $this->order,
                        'user' => $this->user,
                        'orderItems' => json_decode($this->order->items, true)
                    ]);
    }
}