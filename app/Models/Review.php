<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
      protected $fillable = [
            'order_id',
            'chef_id',
            'user_id',
            'food_items_id',
            'review_text',
            'is_approved',
        ];
    
        protected $casts = [
            'food_items_id' => 'array', // json array automatically
        ];

    public function chef() {
        return $this->belongsTo(Chef::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
