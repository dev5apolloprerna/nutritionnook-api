<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodAddon extends Model
{
    protected $fillable = ['foods_id', 'name', 'price'];
}
