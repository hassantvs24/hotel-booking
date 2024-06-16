@props(['value' => null])

<td {{ $attributes->merge([ 'class' => 'text-nowrap' ]) }}>
    @if($value)
        <p class="text-sm font-weight-bold mb-0">{{ $value }}</p>
    @endif

    @if($slot->isNotEmpty())
        {{ $slot }}
    @endif
</td>