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
        return static::settings()[$key] ?: null;
    }

    public static function settings($group = null, $groupBy = false) : array
    {
        $settings = Setting::query();
        if ($group) {
            $settings = $settings->where('group', $group);
        }
        $settings = $settings->get();
        if ($groupBy) {
            return $settings->groupBy('group')->toArray();
        }
        return $settings->pluck('value', 'key')->toArray();
    }
}