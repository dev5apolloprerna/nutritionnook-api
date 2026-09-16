<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            return Setting::getValue($key, $default);
        });
    }

    public static function getRadius(): float
    {
        return (float) self::get('radius_km', 10);
    }

    public static function clearCache(string $key): void
    {
        Cache::forget("setting.{$key}");
    }
}
