@props([ 'title' => null ])

@php
    $styles = config('site.assets.portal.css');
    $scripts = config('site.assets.portal.js');

    $filterNotExists = config('site.portal_filter_not_exists', []);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        @if($title)
            {{ $title }} || {{ config('site.site_info.name') }}
        @else
            {{ config('site.site_info.name') }}
        @endif
    </title>
<!-- Styles -->
    @foreach($styles as $style)
        @php
            $path = $style['type'] == 'local' ? asset_path('css/'.$style['src'], 'portal') : $style['src'];
        @endphp
        <link href="{{ $path }}" rel="{{ $style['rel'] }}" @isset($style['id'])id="{{$style['id']}}"@endisset/>
    @endforeach

    @stack('styles')

</head>
<body>
    <x-portal.shared.navbar is-home="{{ request()->path() === '/' }}"/>

    @if(!in_array(request()->path(), $filterNotExists))
        <x-portal.shared.filter title="{{$title}}" />
    @endif

    {{ $slot }}

    <x-portal.shared.footer />

<!-- Scripts -->
@foreach($scripts as $script)
    @php
        $path = $script['type'] == 'local' ? asset_path('js/'.$script['src'], 'portal') : $script['src'];
    @endphp

    <script src="{{ $path }}"></script>
@endforeach

    @stack('scripts')
</body>
</html>
