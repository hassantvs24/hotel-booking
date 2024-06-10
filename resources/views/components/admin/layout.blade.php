@props([ 'title' => null ])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset_path('img/apple-icon.png') }}">
    <link rel="icon" type="image/png" href="{{ asset_path('img/favicon.png') }}">
    <title>
        @if($title)
            {{ $title }} || {{ config('site.site_info.name') }}
        @else
            {{ config('site.site_info.name') }}
        @endif
    </title>

    @php
        $styles = config('site.assets.admin.css');
        $scripts = config('site.assets.admin.js');
    @endphp

            <!-- Styles -->
    @foreach($styles as $style)
        @php
            $path = $style['type'] == 'local' ? asset_path('css/'.$style['src']) : $style['src'];
        @endphp
        <link href="{{ $path }}" rel="{{ $style['rel'] }}" @isset($style['id'])id="{{$style['id']}}"@endisset/>
    @endforeach

    @stack('styles')
</head>

<body class="g-sidenav-show   bg-gray-100">
<div class="min-height-300 bg-primary position-absolute w-100"></div>

<! -- Sidebar -->
<x-admin.layouts.sidebar/>

<main class="main-content position-relative border-radius-lg ">

    <!-- Navbar -->
    <x-admin.layouts.navbar/>
    <!-- End Navbar -->

    <div class="container-fluid py-4">

        {{ $slot }}

        <x-admin.layouts.footer />
    </div>
</main>

<!-- Scripts -->
@foreach($scripts as $script)
    @php
        $path = $script['type'] == 'local' ? asset_path('js/'.$script['src']) : $script['src'];
    @endphp

    <script src="{{ $path }}"></script>
@endforeach

@stack('scripts')

</body>

</html>