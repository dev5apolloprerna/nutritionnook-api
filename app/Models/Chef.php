<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use NumberFormatter;
use Carbon\Carbon;


class Chef extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
    'name', 'email', 'phone_number', 'dob', 'gender', 'about_chef',
    'profile_image', 'cover_image', 'commission',
    'kitchen_name', 'shop_plot_number', 'floor', 'building_name', 
    'city', 'pincode', 'address',
    'kitchen_type', 'fssai_license_number','account_holder_name', 'fssai_validity_date',
    'opening_time', 'closing_time', 'working_days',
    'bank_name', 'account_number', 'ifsc_code', 'pan_card',
    'personal_document_type', 'personal_documents',
    'fscai_certificate', 'self_declaration',
    'cuisine_speciality', 'preference_tags',
    'kitchen_assessment_photographs', 'certifications',
    'chef_training', 'onboarding_kit_receipt','is_pre_order',
    'latitude','longitude','delivery_radius'
];
    
    protected $casts = [
        'working_days' => 'array',
    ];

    /* ---------------- Relationships ---------------- */

    public function restaurantTypes()
    {
        return $this->belongsToMany(RestaurantType::class, 'chef_restaurant_type');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /* ---------------- Computed Attributes ---------------- */



/* ---------------- Computed Attributes ---------------- */

public function getEarningsAttribute(): string
{
    // "Today's earnings" = the chef's net payout (90% of menu base price) for
    // orders DELIVERED today — same basis as the admin Payout page and the
    // "Manage Earning > Overview > Today's" card. Rounded to whole rupees so it
    // matches the Overview card (which the app parses as a strict integer).
    $orders = $this->orders()
        ->where('status', 'delivered')
        ->whereDate('date', Carbon::today())
        ->get();

    $net = round(\App\Helpers\CommonHelper::chefPayoutBreakdown($orders, $this->commission ?? 0)['net_payout']);

    $formatter = new NumberFormatter('en_IN', NumberFormatter::CURRENCY);
    return $formatter->formatCurrency($net, 'INR');
}

public function getOrdersCountAttribute(): int
{
    return $this->orders()
        ->whereDate('date', Carbon::today())
        ->where('is_buffer','1')
         ->where('payment_status','received')
        ->count();
}

public function getNewOrdersAttribute(): int
{
    return $this->orders()
        ->whereDate('date', Carbon::today())
        ->where('status', 'new')
        ->where('payment_status','received')
        ->where('is_buffer','1')
        ->count();
}

public function getPreparingDishesAttribute(): int
{
    return $this->orders()
        ->where('status', 'preparing')
        ->whereDate('date', Carbon::today())
        ->where('status', 'preparing')
        ->count();
}

public function getReadyDishesAttribute(): int
{
    return $this->orders()
        ->whereDate('date', Carbon::today())
        ->where('status', 'ready')
        ->count();
}

public function getDeliveredDishesAttribute(): int
{
    return $this->orders()
        ->whereDate('date', Carbon::today())
        ->where('status', 'delivered')
        ->count();
}

public function getTomorrowOrdersAttribute(): int
{
    return $this->orders()
        ->whereDate('date', Carbon::tomorrow())
        ->count();
}


    /* ---------------- Auto-append to JSON ---------------- */

    
    protected $appends = [
    'ordersCount',
    'earnings',
    'newOrders',
    'preparingDishes',
    'readyDishes',
    'deliveredDishes',
    'tomorrowOrders',
];

}
