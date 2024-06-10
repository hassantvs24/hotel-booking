@php
    $sidebarItems = config('site.sidebar.items');
@endphp


<aside class="sidenav bg-white navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-4 " id="sidenav-main">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href="{{ route('admin.dashboard') }}">
            <img src="{{ asset_path('img/logo-ct-dark.png') }}" class="navbar-brand-img h-100" alt="main_logo">
            <span class="ms-1 font-weight-bold">
                {{ config('site.site_info.name') }}
            </span>
        </a>
    </div>
    <hr class="horizontal dark mt-0">
    <div class="collapse navbar-collapse  w-auto h-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            @foreach($sidebarItems as $key => $item) @if( count($item['children']) > 0)
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarUrl{{$key}}" class="nav-link {{ request()->is($item['key']) ? 'active' : '' }}" aria-controls="sidebarUrl{{$key}}" role="button" aria-expanded="false">
                        <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                            <i class="{{ $item['icon'] }} text-sm opacity-10"></i>
                        </div>
                        <span class="nav-link-text ms-1">
                                {{ $item['name'] }}
                            </span>
                    </a>
                    <div class="collapse  {{ request()->is($item['key']) ? 'show' : '' }} " id="sidebarUrl{{$key}}">
                        <ul class="nav ms-4">
                            @foreach($item['children'] as $childKey => $child) @if(isset($child['children']) && count($child['children']) > 0)
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="collapse" aria-expanded="false" href="#subSubMenu{{ $childKey }}">
                                        <span class="sidenav-mini-icon"> {{ first_letter($child['name']) }} </span>
                                        <span class="sidenav-normal"> {{ $child['name'] }} <b
                                                    class="caret"></b></span>
                                    </a>
                                    <div class="collapse " id="subSubMenu{{ $childKey }}">
                                        <ul class="nav nav-sm flex-column">
                                            @foreach($child['children'] as $subChildKey => $subChild)
                                                <li class="nav-item ">
                                                    <a class="nav-link" href="{{ $subChild['route'] ? route($subChild['route']) : '#'}}">
                                                        <span class="sidenav-mini-icon"> {{ first_letter($subChild['name']) }} </span>
                                                        <span class="sidenav-normal"> {{ $subChild['name'] }} </span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </li>
                            @else
                                <li class="nav-item ">
                                    <a class="nav-link {{request()->path() === $child['key'] ? 'active' : ''}}" href="{{ $child['route'] ? route($child['route']) : '#'}}">
                                        <span class="sidenav-mini-icon">{{ first_letter($child['name']) }}</span>
                                        <span class="sidenav-normal"> {{ $child['name'] }}</span>
                                    </a>
                                </li>
                            @endif @endforeach
                        </ul>
                    </div>
                </li>
            @else
                <li class="nav-item">
                    <a class="nav-link {{ request()->is($item['key']) ? 'active' : '' }}" href="{{ $item['route'] ? route($item['route']) : '#' }}">
                        <div class="icon icon-shape icon-sm text-center  me-2 d-flex align-items-center justify-content-center">
                            <i class="{{ $item['icon'] }} text-sm"></i>
                        </div>
                        <span class="nav-link-text ms-1">
                                {{ $item['name'] }}
                            </span>
                    </a>
                </li>
            @endif @endforeach
        </ul>
    </div>

    <hr class="horizontal dark">
    <div class="sidenav-footer mx-3 my-3">
        <a href="{{ url('/') }}" target="_blank" class="btn btn-dark btn-sm w-100 mb-3">
            Visit Site
        </a>
    </div>
</aside>