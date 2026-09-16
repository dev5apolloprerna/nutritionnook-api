<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = ['restaurant_id', 'title', 'description', 'discount_type', 'discount_value', 'start_date', 'end_date', 'status'];

}
