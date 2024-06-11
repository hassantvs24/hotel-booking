@props(['value' => null])

<th {{ $attributes->merge([ 'class' => 'text-uppercase text-secondary text-xxs font-weight-bolder opacity-7' ]) }} >
    @if($value)
        {{ $value }}
    @endif

    @if($slot->isNotEmpty())
        {{ $slot }}
    @endif
</th>