<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FoodDish extends Model
{
    use HasFactory;

    protected $table = 'food_dishes';

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'category_id',
        'spicy_level',
        'weight_option_id',
        'preparation_time_id',
        'is_active',
        // 'availability_type',
        // 'is_top_picks',
        'chef_id',
        'ingredients',
        'allergy_warning',
        'cuisine_type_id',
        'is_get_now_or_get_later',
        'tags',
        'in_stock',
        
    ];

    protected $casts = [
        // 'is_active' => 'boolean',
        'is_top_picks' => 'boolean',
        'price' => 'decimal:2',
    ];

    // 🔁 Relationships

    public function chef()
    {
        return $this->belongsTo(Chef::class, 'chef_id');
    }
    // public function categories()
    // {
    //     return $this->belongsToMany(Category::class, 'category_food_dishes');
    // }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_food_dishes', 'food_dish_id', 'category_id');
    }
    
    public function cuisineType()
    {
        return $this->belongsTo(CuisineType::class, 'cuisine_type_id');
    }
    
public function getCategoriesAttribute()
{
    if (!$this->category_id) {
        return collect([]);
    }

    $ids = explode(',', $this->category_id);

    return \App\Models\Category::whereIn('id', $ids)->get();
}

}
