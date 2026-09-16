<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FoodItem extends Model
{
    protected $table = "food_dishes";
    
    
    protected $fillable = [
        'id',
        'chef_id',
        'name',
        'description',
        'image',
        'price',
        'category_id',
        'preparation_time_id',
        'weight_option_id',
        'ingredients',
        'allergy_warning',
        'category_id',
        'is_active',
        'spicy_level',
        'cuisine_type_id',
        'is_get_now_or_get_later',
        'tags',
        'base_price',
        'secuirity_deposite'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

   public function categories()
{
    return $this->belongsToMany(
        Category::class,          // Related model
        'category_food_dishes',   // Pivot table name
        'food_dish_id',           // Foreign key on pivot pointing to this model
        'category_id'             // Foreign key on pivot pointing to related model
    );
}


    public function cuisineTypes()
    {
        return $this->belongsToMany(CuisineType::class, 'cuisine_food_item');
    }

    public function preferences()
    {
        return $this->belongsToMany(Preference::class, 'food_item_preference');
    }

    public function mealTimes()
    {
        return $this->belongsToMany(MealTime::class, 'food_item_meal_time');
    }
}
