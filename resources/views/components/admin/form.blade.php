@props(['action' => '', 'method' => 'POST'])

@php
    $method = strtoupper($method);
@endphp

<form {{ $attributes->merge(['class' => '', 'method' => 'POST', 'action' => $action]) }}>
    @csrf
    @if (!in_array($method, ['GET', 'POST']))
        @method($method)
    @endif

    {{ $slot }}
</form>