<?php

if (!function_exists('asset_path')) {
    function asset_path($path, $type = 'admin') : string
    {
        return asset('assets/' . $type . '/' . $path);
    }
}

// get the first letter of a string
if (!function_exists('first_letter')) {
    function first_letter($string) : string
    {
        return strtoupper(substr($string, 0, 1));
    }
}

// format date
if (!function_exists('format_date')) {
    function format_date($date, $format = 'd M Y') : string
    {
        return date($format, strtotime($date));
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission) : bool
    {
        return auth()->user()->hasPermission($permission);
    }
}
if(!function_exists('truncate_Words')){
    function truncate_Words($text, $limit)
    {
        $words = explode(' ', $text);
        if (count($words) <= $limit) {
            return $text;
        }
        return implode(' ', array_slice($words, 0, $limit)) . '...';
    }
}

if(!function_exists('getStoragePath')){
    function getStoragePath($path) : string
    {
        return url('storage/' . $path);
    }
}

if (!function_exists('getSetting')) {
    function getSetting($key) : ?string
    {
        $setting = \App\Helpers\Helper::getSetting($key);
        return $setting;
    }
}

if (!function_exists('getSiteLogo')) {
    function getSiteLogo() : string
    {
        $logo = null;

        if (!$logo) {
            $logo = asset('assets/logo/logo.png');
        }

        return $logo;
    }
}

