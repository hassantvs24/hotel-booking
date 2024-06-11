<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red Locker | @yield('title')</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{asset('assets/portal/css/bootstrap.min.css')}}">
    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="{{asset('assets/portal/css/jquery-ui.css')}}">
    <!-- Style CSS -->
    <link rel="stylesheet" href="{{asset('assets/portal/css/style.css')}}">
    <!-- Responsive CSS -->
    <link rel="stylesheet" href="{{asset('assets/portal/css/responsive.css')}}">

</head>
<body>


    @if (trim($__env->yieldContent('title')) === 'Home')
        @include('portal.layout.inc.homeheader')
    @else
        @include('portal.layout.inc.header')
    @endif

    @yield('content')


@include('portal.layout.inc.footer')






<!-- jQuery Library -->
<script src="{{ asset('assets/portal/js/jquery.min.js') }}"></script>
<!-- Bootstrap Bundle JS -->
<script src="{{ asset('assets/portal/js/bootstrap.bundle.min.js') }}"></script>
<!-- jQuery Easing -->
<script src="{{ asset('assets/portal/js/jquery.easing.min.js') }}"></script>
<!-- jQuery UI -->
<script src="{{ asset('assets/portal/js/jquery-ui.js') }}"></script>
<!-- jQuery Main -->
<script src="{{ asset('assets/portal/js/main.js') }}"></script>
</body>
</html>
