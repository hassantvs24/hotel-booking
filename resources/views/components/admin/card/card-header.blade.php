@props(['title' => null, 'icon' => null])

<div {{ $attributes->merge(['class' => 'card-header pb-0 pt-3 bg-transparent']) }}>
    @if($title)
        <h6 class="text-capitalize">{{ $title }}</h6>
    @endif

    @if($title && $icon)
        <p class="text-sm mb-0">
            <i class="{{ $icon }} text-success"></i>
        </p>
    @endif

    @if($slot->isNotEmpty())
        {{ $slot }}
    @endif
</div>