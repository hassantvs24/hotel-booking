<div {{ $attributes->merge(['class' => 'card mb-4 ' . $additionalClasses]) }}>
    <div class="card-body p-3">
        <div class="row">
            <div class="col-8">
                <div class="numbers">
                    <p class="text-sm mb-0 text-uppercase font-weight-bold">{{ $title }}</p>
                    <h5 class="font-weight-bolder">
                        {{ $amount }}
                    </h5>
                    <p class="mb-0">
                        <span class="text-success text-sm font-weight-bolder">{{ $percentage }}</span>
                        {{ $description }}
                    </p>
                </div>
            </div>
            <div class="col-4 text-end">
                <div class="icon icon-shape {{ $iconBgClass }} shadow-primary text-center rounded-circle">
                    <i class="{{ $iconClass }} text-lg opacity-10" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
</div>
