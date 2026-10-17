<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\Portal\LocalConfirmController;
use App\Models\Booking;
use App\Models\BookingGroup;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

/**
 * Local machines never receive SSLCommerz's callback (IPN), so a payment made on the sandbox page is not noticed
 * unless the guest lands back on the site's success page. This asks SSLCommerz about every booking still waiting
 * for payment, and confirms the ones that were really paid — with the bank's own reference, which is what a
 * refund needs. A booking nobody paid for is left alone (this never fakes a payment).
 */
class PickUpSandboxPayments extends Command
{
    protected $signature = 'payments:pickup-sandbox {--days=3 : only look at bookings made in the last N days}';

    protected $description = 'Confirm bookings that were really paid on the SSLCommerz sandbox page (needed on a local machine)';

    public function handle(LocalConfirmController $confirm): int
    {
        $since = now()->subDays((int) $this->option('days'));

        $groups = BookingGroup::where('payment_status', '!=', 'paid')->where('created_at', '>=', $since)->pluck('group_ref');
        $single = Booking::whereNull('booking_group_id')->where('status', 'reserved')
            ->where('payment_status', '!=', 'paid')->where('created_at', '>=', $since)->pluck('booking_number');

        $references = $groups->concat($single)->map(fn ($ref) => (string) $ref);

        if ($references->isEmpty()) {
            $this->info('No booking is waiting for payment.');

            return self::SUCCESS;
        }

        $confirmed = 0;

        foreach ($references as $reference) {
            $result = $confirm->confirmRealPayment($reference, Request::create('/pickup', 'POST'));

            if ($result) {
                $confirmed++;
                $this->line("  <info>PAID</info>      {$reference}  — confirmed with the real SSLCommerz payment");
            } else {
                $this->line("  not paid  {$reference}  — SSLCommerz has no valid payment for it yet");
            }
        }

        $this->newLine();
        $this->info("Checked {$references->count()} booking(s), confirmed {$confirmed}.");

        return self::SUCCESS;
    }
}
