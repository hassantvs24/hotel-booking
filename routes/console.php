<?php

use App\Console\Commands\DeleteExpiredHotelRequests;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


// OFF ON PURPOSE. This old command soft-deletes every bid whose expiry has passed (Done and Declined ones too, which
// wipes the bid history and the 3-offer count), and it currently only survives because RoomRequest has no
// `room_request_accepteds` relation, so it crashes before deleting anything. Bid expiry is handled safely by
// ExpireRoomRequests below (it marks Timeout and never deletes). The booking-request table it also covered is empty.
// Schedule::command(DeleteExpiredHotelRequests::class)->everyFiveSeconds();

Schedule::job(new \App\Jobs\ExpireCartItems)->everyFiveMinutes();
Schedule::job(new \App\Jobs\ExpireUnpaidBookings)->everyFiveMinutes();
// A guest who paid and never came back to the site (closed the tab...) is found by asking SSLCommerz, so a paid booking is never left waiting
Schedule::command('payments:pickup-sandbox --days=1')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
Schedule::job(new \App\Jobs\ExpireRoomRequests)->everyFiveMinutes();

// Empty property drafts (nothing typed) left alone for 3 days; a draft with the owner's own content is never removed here
Schedule::job(new \App\Jobs\DeleteEmptyDrafts)->dailyAt('03:30');
// SSLCommerz finishes a refund within minutes (sandbox) — ask often so the page and the email do not lag behind.
// It only calls SSLCommerz when a refund is actually waiting, so running it every minute costs nothing otherwise.
Schedule::job(new \App\Jobs\CheckRefundStatuses)->everyMinute();

// The money ledger: book every new payment and completed refund (see App\Services\Money\LedgerSync)
// (dispatchSync: it runs right here, so it works even when the site's queue has no worker running)
Schedule::call(fn () => \App\Jobs\SyncLedger::dispatchSync())->name('money-ledger-sync')->everyMinute()->withoutOverlapping();
