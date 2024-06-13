@props(['value'])

@php
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

    $status = strtolower($value);

    $statusButton = 'btn-outline-success';
    $statusIcon = 'fa-check';

    if (array_key_exists($status, $acceptedStatuses)) {
        $statusButton = $acceptedStatuses[$status];
        $statusIcon = $statusIcons[$status];
    }

    $status = ucfirst($status);
@endphp

<div class='d-flex align-items-center'>
    <span class='btn btn-icon-only {{ $statusButton }} btn-rounded mb-0 me-2 btn-sm d-flex align-items-center justify-content-center'>
        <i class='fas {{ $statusIcon }}' aria-hidden='true'></i>
    </span>
    <span>{{ $status }}</span>
</div>