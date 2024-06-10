@props([
    'title' => null,
    'value' => null,
    'description' => null,
    'iconClass' => null,
    'iconBgClass' => null
])

@php
    $iconClass = $iconClass ?: 'fa fa-chart-bar';
    $iconBgClass = $iconBgClass ?: 'bg-dark';
@endphp

<x-admin.card>
    <x-admin.card.card-body class="p-3">
        @if($title && $value || $title && $description)
            <div class="row">
                <div class="col-8">
                    <div class="numbers">
                        <p class="text-sm mb-0 text-uppercase font-weight-bold">{{ $title }}</p>
                        <h5 class="font-weight-bolder">
                            {{ $value }}
                        </h5>

                        @if($title && $description)
                            <p class="mb-0">
                                {{ $description }}
                            </p>
                        @endif

                    </div>
                </div>

                @if($title && $iconClass)
                    <div class="col-4 text-end">
                        <div class="icon icon-shape {{ $iconBgClass }} shadow-primary text-center rounded-circle">
                            <i class="{{ $iconClass }} text-lg opacity-10" aria-hidden="true"></i>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if($slot->isNotEmpty())
            {{ $slot }}
        @endif
    </x-admin.card.card-body>
</x-admin.card>