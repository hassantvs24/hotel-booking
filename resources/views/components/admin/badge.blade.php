@props(['type' => 'success', 'size' => 'md', 'text' => '', 'icon' => ''])

@php
    $typeClasses = [
        'success'       => 'badge-success',
        'info'          => 'badge-info',
        'warning'       => 'badge-warning',
        'danger'        => 'badge-danger',
        'primary'       => 'badge-primary',
        'secondary'     => 'badge-secondary',
    ][$type ?: 'success'];

    $sizeClasses = [
        'sm' => 'badge-sm',
        'md' => 'badge-md',
        'lg' => 'badge-lg',
    ][$size ?: 'md'];
@endphp

<span {{ $attributes->merge(['class' => "badge $typeClasses $sizeClasses"]) }}>
    @if($icon)
        <i class="{{ $icon }} mr-2"></i>
    @endif
    @if(trim($slot) !== '')
        {{ $slot }}
    @else
        {{ $text }}
    @endif
</span>