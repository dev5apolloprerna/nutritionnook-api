<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'coupons';

    protected $fillable = [
        'code', 'type', 'value', 'min_order_value', 'max_discount_amount','usage_limit', 
        'usage_limit_per_user',  'starts_at', 'expires_at',
        'is_active','description'
    ];
    
    public function foodDish()
    {
        return $this->belongsTo(FoodDish::class, 'food_dish_id');
    }

    public function chef()
    {
        return $this->belongsTo(Chef::class, 'chef_id');
    }
}
