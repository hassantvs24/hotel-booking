@php
    $developerInfo = config('site.developed_by');
@endphp
<footer class="footer pt-3  ">
    <div class="container-fluid">
        <div class="row align-items-center justify-content-lg-between">
            <div class="col-lg-6 mb-lg-0 mb-4"></div>
            <div class="col-lg-6">
                <div class="copyright text-sm text-muted d-flex justify-content-end">
                    Copyright
                    © {{ date('Y') }}.
                    Developed By &nbsp;
                    <a href="{{ $developerInfo['company_website'] }}"
                       class="font-weight-bold"
                       target="_blank">
                        {{ $developerInfo['company'] }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>