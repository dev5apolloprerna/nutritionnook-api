<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Allergy extends Model
{
    use SoftDeletes;

    protected $table = 'allergies';

    protected $fillable = [
        'title',
        'status'
    ];

    protected $dates = ['deleted_at'];
}