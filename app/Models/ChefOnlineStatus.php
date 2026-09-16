<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChefOnlineStatus extends Model
{
    protected $fillable = ['user_id', 'is_open'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
