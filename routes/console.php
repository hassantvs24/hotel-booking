<?php

use App\Console\Commands\DeleteExpiredHotelRequests;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::command(DeleteExpiredHotelRequests::class)->everyFiveSeconds();

Schedule::job(new \App\Jobs\ExpireCartItems)->everyFiveMinutes();
Schedule::job(new \App\Jobs\ExpireUnpaidBookings)->everyFiveMinutes();
//Schedule::job(new \App\Jobs\ExpireRoomRequests)->everyFiveMinutes();
