<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'title',
        'status',
        'image'
    ];

    public function dishes()
    {
        return $this->belongsToMany(FoodDish::class, 'category_food_dishes');
    }
}
