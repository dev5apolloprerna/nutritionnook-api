<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'chef_id',
        'total_earning',
        'commission_amount',
        'payout_amount',
        'razorpay_payout_id',
        'razorpay_fund_account_id',
        'status',
        'payout_month',
        'payout_year',
        'order_count',
        'failure_reason',
        'payment_method',
        'transaction_id',
    ];

    protected $casts = [
        'total_earning' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'payout_month' => 'integer',
        'payout_year' => 'integer',
        'order_count' => 'integer',
    ];

    public function chef()
    {
        return $this->belongsTo(Chef::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'payout_id');
    }

    public function scopeForMonth($query, $month, $year)
    {
        return $query->where('payout_month', $month)->where('payout_year', $year);
    }

    public function getMonthNameAttribute()
    {
        if ($this->payout_month && $this->payout_year) {
            return date('F Y', mktime(0, 0, 0, $this->payout_month, 1, $this->payout_year));
        }
        return $this->created_at ? $this->created_at->format('F Y') : 'N/A';
    }
}
