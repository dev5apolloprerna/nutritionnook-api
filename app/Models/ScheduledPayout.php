<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledPayout extends Model
{
    protected $fillable = [
        'payout_date',
        'status'
    ];
}
