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
