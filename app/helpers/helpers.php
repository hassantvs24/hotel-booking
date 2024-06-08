<?php

if (!function_exists('asset_path')) {
    function asset_path($path, $type = 'admin') : string
    {
        return asset('assets/' . $type . '/' . $path);
    }
}
