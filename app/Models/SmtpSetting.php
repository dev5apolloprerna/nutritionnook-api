<?php
// app/Models/SmtpSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmtpSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'smtp_settings';

    protected $fillable = [
        'mailer',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
        'status',
        'is_default'
    ];

    protected $hidden = [
        'password'
    ];

    // Boot method to handle default setting logic
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // If this is set as default, remove default from others
            if ($model->is_default && $model->status === 'active') {
                static::where('id', '!=', $model->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    // Get default SMTP configuration
    public static function getDefault()
    {
        return static::where('status', 'active')
            ->where('is_default', true)
            ->first();
    }

    // Apply SMTP configuration dynamically
    public function applyConfig()
    {
        config([
            'mail.default' => $this->mailer ?? 'smtp',
            'mail.mailers.smtp.host' => $this->host,
            'mail.mailers.smtp.port' => $this->port,
            'mail.mailers.smtp.encryption' => $this->encryption,
            'mail.mailers.smtp.username' => $this->username,
            'mail.mailers.smtp.password' => $this->password,
            'mail.from.address' => $this->from_address,
            'mail.from.name' => $this->from_name,
        ]);
    }
}