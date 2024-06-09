<li class="mb-2">
    <a class="dropdown-item {{ $class }}" href="javascript:;">
        <div class="d-flex py-1">
            @if($imgSrc)
                <div class="my-auto">
                    <img src="{{ $imgSrc }}" class="avatar avatar-sm me-3 my-auto" alt="user image">
                </div>
            @elseif($svgIcon)
                <div class="avatar avatar-sm me-3 bg-gradient-secondary my-auto">
                    {!! $svgIcon !!}
                </div>
            @endif
            <div class="d-flex flex-column justify-content-center">
                <h6 class="text-sm font-weight-normal mb-1">
                    @if($subtitle)
                        <span class="font-weight-bold">{{ $title }}</span> {{ $subtitle }}
                    @else
                        {{ $title }}
                    @endif
                </h6>
                <p class="text-xs text-secondary mb-0">
                    <i class="fa fa-clock me-1"></i>
                    {{ $time }}
                </p>
            </div>
        </div>
    </a>
</li>
