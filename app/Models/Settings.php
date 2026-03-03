<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'app_name', 'support_email', 'contact_phone', 'website',
        'payment_provider', 'payment_api_key', 'payment_secret_key', 'payment_currency',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
    ];

    protected $casts = [
        'smtp_port' => 'integer',
    ];

    protected $hidden = [
        'payment_secret_key',
        'smtp_password',
    ];

    public static function getSettings(): self
    {
        return self::firstOrCreate([], [
            'app_name' => 'Islamic Learning Platform',
            'payment_currency' => 'USD',
            'smtp_port' => 587,
            'payment_provider' => 'manual',
        ]);
    }
}
