<?php

namespace App\Helpers;

class Helper
{
    // Function to get the client IP address
    public static function getUserIP() : ?string
    {
        return request()->ip();
    }
}