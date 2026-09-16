<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CuisineType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuisine_type';

    protected $fillable = [
        'title',
        'status',
        'image',
    ];

    // public function users()
    // {
    //     return $this->belongsToMany(User::class, 'cuisine_type_user');
    // }
    
    public function foodDishes()
    {
        return $this->hasMany(FoodDish::class, 'cuisine_type_id');
    }
}
