<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'order_id',
        'chef_id',
        'user_id',
        'food_items_id',
        'rating'
    ];

    protected $casts = [
        'food_items_id' => 'array', // JSON <-> Array
    ];

    public function chef() {
        return $this->belongsTo(Chef::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
