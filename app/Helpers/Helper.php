<?php

namespace App\Helpers;

use App\Models\Setting;

class Helper
{
    // Function to get the client IP address
    public static function getUserIP() : ?string
    {
        return request()->ip();
    }

    public static function getCurrencySymbol($currencyCode) : ?string
    {
        $currencies = config('countries.currency');
        return $currencies[$currencyCode];
    }

    public static function getSetting($key) : ?string
    {
        return static::getAllSettings()[$key] ?: null;
    }

    public static function getAllSettings() : array
    {
        $settings = Setting::query()->pluck('value', 'key')->toArray();
        return $settings;
    }
}