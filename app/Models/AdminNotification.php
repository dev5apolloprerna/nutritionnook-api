<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $table = 'admin_notifications';

    protected $fillable = [
        'title', 'body', 'user_type', 'send_to_all', 'user_ids',
        'image_url', 'click_action', 'custom_data',
        'total_tokens', 'success_count', 'failed_count', 'failed_tokens', 'sent_by',
    ];

    protected $casts = [
        'user_ids' => 'array',
        'custom_data' => 'array',
        'failed_tokens' => 'array',
        'send_to_all' => 'boolean',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
