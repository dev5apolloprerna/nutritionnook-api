<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KitchenPhotos extends Model
{
    use HasFactory;

    protected $table = 'kitchen_photos';

    protected $fillable = [
        'chef_id',
        'date',
        'description',
        'images',
        'status',
        'notes',
    ];

    // 🔁 Relationships
    public function chef()
    {
        return $this->belongsTo(User::class, 'chef_id'); // assuming User model handles chefs
    }
}
