<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'full_address',
        'pincode',
        'is_selected',
        'latitude',
        'longitude',
        'house_number',
        'floor',
        'building_name',
        'tag',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
