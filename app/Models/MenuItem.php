<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuItem extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'menu_items';

    protected $fillable = [
        'title',
        'image_url',
        'status'
    ];
}
