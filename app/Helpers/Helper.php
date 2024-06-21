<?php

namespace App\Helpers;

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
}