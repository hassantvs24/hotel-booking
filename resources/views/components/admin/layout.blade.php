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
<div class="min-height-65 bg-primary position-absolute w-100"></div>

<! -- Sidebar -->
<x-admin.layouts.sidebar/>

<main class="main-content position-relative border-radius-lg ">

    <!-- Navbar -->
    <x-admin.layouts.navbar/>
    <!-- End Navbar -->

    <div class="container-fluid py-4">

        <div class="page_container">
            {{ $slot }}
        </div>

        <x-admin.layouts.footer/>
    </div>
</main>

<!-- Scripts -->
@foreach($scripts as $script)
    @php
        $path = $script['type'] == 'local' ? asset_path('js/'.$script['src']) : $script['src'];
    @endphp

    <script src="{{ $path }}"></script>
@endforeach

<script>
    @if (Session::has('message'))
    (function () {
        let type = "{{ Session::get('alert-type', 'info') }}";
        let message = "{{ Session::get('message') }}";

        function showToast(type, message) {
            toastr.options.timeOut = 1000;
            toastr[type](message);
        }

        switch (type) {
            case 'info':
                showToast('info', message);
                break;
            case 'success':
                showToast('success', message);
                break;
            case 'warning':
                showToast('warning', message);
                break;
            case 'error':
                showToast('error', message);
                break;
        }
    })();
    @endif
</script>


@stack('scripts')

</body>

</html>
