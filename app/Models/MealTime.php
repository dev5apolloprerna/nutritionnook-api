<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MealTime extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'meal_times';
    protected $fillable = ['name', 'status'];

    public function foods()
    {
        return $this->hasMany(Food::class, 'meal_times_id');
    }
}
