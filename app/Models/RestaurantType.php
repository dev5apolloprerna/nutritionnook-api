<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RestaurantType extends Model
{
    use HasFactory;

    protected $table = 'restaurant_types';

    protected $fillable = [
        'title',
        'status',
    ];

    public function chefs()
    {
        return $this->belongsToMany(Chef::class, 'chef_restaurant_type');
    }
}
