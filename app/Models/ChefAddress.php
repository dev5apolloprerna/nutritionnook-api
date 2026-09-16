<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChefAddress extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'full_address', 'pincode','latitude',
        'longitude',];

    public function chef()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
