<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'user_id', 'refund_amount',
        'razorpay_payment_id', 'razorpay_refund_id',
        'status', 'reason', 'gateway_response'
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'refund_amount' => 'decimal:2',
    ];

    public function order() { return $this->belongsTo(Order::class); }
    public function user() { return $this->belongsTo(User::class); }
}
