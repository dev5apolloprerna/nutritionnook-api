<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Preference extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'preferences';

    protected $fillable = [
        'name',
        'status',
    ];

    // public function users()
    // {
    //     return $this->belongsToMany(User::class, 'preference_user');
    // }
}
