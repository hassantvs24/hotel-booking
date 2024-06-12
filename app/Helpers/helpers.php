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

if (!function_exists('get_formatted_status')) {
    function get_formatted_status($status) : string
    {
        $acceptedStatuses = [
            'complete' => 'btn-outline-success',
            'active'   => 'btn-outline-success',
            'pending'  => 'btn-outline-warning',
            'failed'   => 'btn-outline-danger',
            'refunded' => 'btn-outline-dark',
        ];

        $statusIcons = [
            'complete' => 'fa-check',
            'active'   => 'fa-check',
            'pending'  => 'fa-hourglass-half',
            'failed'   => 'fa-times',
            'refunded' => 'fa-undo',
        ];

        $status = strtolower($status);

        $statusButton = 'btn-outline-success';
        $statusIcon = 'fa-check';

        if (array_key_exists($status, $acceptedStatuses)) {
            $statusButton = $acceptedStatuses[$status];
            $statusIcon = $statusIcons[$status];
        }

        $status = ucfirst($status);


        return "<div class='d-flex align-items-center'>
                <span class='btn btn-icon-only {$statusButton} btn-rounded mb-0 me-2 btn-sm d-flex align-items-center justify-content-center'>
                    <i class='fas {$statusIcon}' aria-hidden='true'></i>
                </span>
                <span>{$status}</span>
            </div>";
    }
}
