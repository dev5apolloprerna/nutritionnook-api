<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContinuousAudits extends Model
{
    use HasFactory;

    protected $table = 'continuous_audits';

    protected $fillable = [
        'chef_id',
        'date',
        'description',
        'images',
    ];

    // 🔁 Relationships
    public function chef()
    {
        return $this->belongsTo(User::class, 'chef_id'); // assuming User model handles chefs
    }
}
