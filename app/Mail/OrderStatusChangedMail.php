<?php
// app/Mail/OrderStatusChangedMail.php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;
    public $oldStatus;
    public $newStatus;

    public function __construct(Order $order, User $user, $oldStatus, $newStatus)
    {
        $this->order = $order;
        $this->user = $user;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function build()
    {
        $subject = 'Order Status Updated - #' . $this->order->id;
        
        if ($this->newStatus == 'accepted') {
            $subject = 'Order Accepted - #' . $this->order->id;
        } elseif ($this->newStatus == 'rejected') {
            $subject = 'Order Rejected - #' . $this->order->id;
        } elseif ($this->newStatus == 'preparing') {
            $subject = 'Order is Being Prepared - #' . $this->order->id;
        } elseif ($this->newStatus == 'ready') {
            $subject = 'Order Ready for Pickup/Delivery - #' . $this->order->id;
        } elseif ($this->newStatus == 'delivered') {
            $subject = 'Order Delivered - #' . $this->order->id;
        } elseif ($this->newStatus == 'cancelled') {
            $subject = 'Order Cancelled - #' . $this->order->id;
        }

        return $this->subject($subject)
                    ->view('emails.order-status-changed')
                    ->with([
                        'order' => $this->order,
                        'user' => $this->user,
                        'oldStatus' => $this->oldStatus,
                        'newStatus' => $this->newStatus,
                        'orderItems' => json_decode($this->order->items, true)
                    ]);
    }
}