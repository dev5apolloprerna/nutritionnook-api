<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'heading',
        'description',
        'image',
        'status',
    ];

    
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        // Support both legacy "public/images/..." values and the current "images/..." format.
        $path = preg_replace('#^public/#', '', ltrim($this->image, '/'));

        return asset($path);
    }
}
