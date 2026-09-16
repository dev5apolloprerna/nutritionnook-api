<?php
// app/Mail/OrderRefundMail.php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderRefundMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;
    public $refund;
    public $refundAmount;

    public function __construct(Order $order, User $user, Refund $refund, $refundAmount)
    {
        $this->order = $order;
        $this->user = $user;
        $this->refund = $refund;
        $this->refundAmount = $refundAmount;
    }

    public function build()
    {
        return $this->subject('Refund Processed for Order #' . $this->order->id)
                    ->view('emails.order-refund')
                    ->with([
                        'order' => $this->order,
                        'user' => $this->user,
                        'refund' => $this->refund,
                        'refundAmount' => $this->refundAmount
                    ]);
    }
}