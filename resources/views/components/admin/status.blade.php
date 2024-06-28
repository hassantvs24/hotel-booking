@props(['value'])

@php
    $type = [
        'active'        => 'success',
        'inactive'      => 'danger',
        'draft'         => 'warning',
        'pending'       => 'warning',
        'published'     => 'success',
        'unpublished'   => 'danger',
        'approved'      => 'success',
        '1'             => 'success',
        '0'             => 'danger',
    ][strtolower($value) ?: 'success'];
@endphp

<x-admin.badge :text="$value" :type="$type" size="md"/>