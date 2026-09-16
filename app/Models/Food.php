<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $table = 'foods';
    protected $fillable = [
        'restaurant_id', 'meal_times_id', 'name', 'description', 'price', 'discount_price', 'image', 'is_veg', 'spicy_level', 'status'
    ];

    public function preferences() {
        return $this->belongsToMany(Preference::class, 'food_preferences', 'foods_id', 'preference_id');
    }

    public function variants() {
        return $this->hasMany(FoodVariant::class, 'foods_id');
    }

    public function addons() {
        return $this->hasMany(FoodAddon::class, 'foods_id');
    }

    public function offers() {
        return $this->belongsToMany(Offer::class, 'food_offers', 'foods_id', 'offer_id');
    }
}
