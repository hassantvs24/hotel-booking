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

// get active class based on route group
if (!function_exists('active_class')) {
    function active_class($group) : string
    {
        return request()->is($group) ? 'active' : '';
    }
}
