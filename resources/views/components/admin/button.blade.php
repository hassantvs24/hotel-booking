@props(['type' => 'button', 'size' => 'md', 'variant' => 'outline-primary', 'text' => '', 'icon' => ''])

@php
    $sizeClasses = [
        'xs' => 'btn-xs',
        'sm' => 'btn-sm',
        'md' => 'btn-md',
        'lg' => 'btn-lg',
    ][$size ?? 'md'];

    $styleClasses = [
        'primary'           => 'btn-primary',
        'secondary'         => 'btn-secondary',
        'success'           => 'btn-success',
        'danger'            => 'btn-danger',
        'warning'           => 'btn-warning',
        'info'              => 'btn-info',
        'light'             => 'btn-light',
        'dark'              => 'btn-dark',
        'outline-primary'   => 'btn-outline-primary',
        'outline-secondary' => 'btn-outline-secondary',
        'outline-success'   => 'btn-outline-success',
        'outline-danger'    => 'btn-outline-danger',
        'outline-warning'   => 'btn-outline-warning',
        'outline-info'      => 'btn-outline-info',
        'outline-light'     => 'btn-outline-light',
        'outline-dark'      => 'btn-outline-dark',
    ][$variant ?? 'outline-primary'];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "btn $sizeClasses $styleClasses mb-0"]) }}>
    @if($icon)
        <i class="{{ $icon }} mr-2"></i>
    @endif
    {{ $slot->isEmpty() ? $text : $slot }}
</button>