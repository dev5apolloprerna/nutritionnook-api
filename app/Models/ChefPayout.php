<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChefPayout extends Model
{
    protected $table = "chef_payouts";
    
    protected $fillable = [
        'chef_id',
        'gross_amount',
        'commission_amount',
        'net_amount',
        'bank_name',
        'account_number',
        'ifsc_code',
        'razorpay_payout_id',
        'status',
        'failure_reason',
    ];

    public function chef()
    {
        return $this->belongsTo(Chef::class);
    }
}
