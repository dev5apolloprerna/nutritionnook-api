<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'chef_id', 'user_id', 'items', 'amount', 'date', 'status', 'payment','is_refunded','refunded_at','rejected_by','accepted_at','name','price','image','description','platform_fee','calculated_gst','discount_amount','security_deposit','is_payout_completed','payout_id'
    ];
    
     protected $casts = [
        'items'   => 'array',
        'payment' => 'array', // ✅ this makes it return as JSON object automatically
    ];

    public function chef()
    {
        return $this->belongsTo(Chef::class);
    }
    
    public function dish() {
    return $this->belongsTo(FoodDish::class, 'dish_id', 'id'); 
    // જો તમારી કોલમનું નામ અલગ હોય તો તે મુજબ (દા.ત. item_id)
}

    public function refund()
{
    return $this->hasOne(Refund::class);
}

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
