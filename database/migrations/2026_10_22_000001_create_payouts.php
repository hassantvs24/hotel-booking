<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payouts: what the platform pays out to each vendor.
     *
     * - A statement is one payout to one vendor: a list of ledger lines (payments and refunds) and their total.
     * - A statement is a DRAFT until the admin has sent the money and marks it PAID. A paid statement is locked.
     *   A mistake is fixed by REVERSING it (it stays on record, and its lines become payable again), never by editing it.
     * - A ledger line can belong to only one live statement. The database enforces it: (ledger_entry_id, active) is unique,
     *   and `active` is 1 while the line is in a draft or paid statement, and NULL once the statement is reversed/cancelled.
     * - Lines keep their own copy of the amounts, so a statement still reads the same later.
     * - Nothing here has a foreign key that cascades: no deletion can ever remove payout history.
     */
    public function up(): void
    {
        Schema::create('payout_statements', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->nullable()->unique();           // ST-000012, set when the statement is created
            $t->unsignedBigInteger('vendor_id')->index();
            $t->string('vendor_name')->nullable();
            $t->string('status', 12)->index();                        // draft | paid | reversed | cancelled
            $t->unsignedInteger('bookings_count')->default(0);
            $t->unsignedInteger('lines_count')->default(0);
            $t->bigInteger('gross_paisa')->default(0);                // what guests paid for these lines (after refunds)
            $t->bigInteger('vat_paisa')->default(0);
            $t->bigInteger('platform_service_paisa')->default(0);
            $t->bigInteger('commission_paisa')->default(0);
            $t->bigInteger('vendor_paisa')->default(0);               // the amount to pay the vendor
            $t->date('payable_until')->nullable();                    // check-outs up to this day were included

            $t->unsignedBigInteger('created_by')->nullable();
            $t->string('created_by_name')->nullable();

            $t->dateTime('paid_at')->nullable();
            $t->unsignedBigInteger('paid_by')->nullable();
            $t->string('paid_by_name')->nullable();
            $t->string('method', 20)->nullable();                     // bank | bkash | nagad | rocket | cash | other
            $t->string('reference', 120)->nullable();                 // the bank / bKash transaction number
            $t->text('note')->nullable();
            $t->json('account_snapshot')->nullable();                 // the vendor's payout account as it was when this was paid
            $t->boolean('account_recently_changed')->default(false);  // the admin was warned and confirmed
            $t->dateTime('emailed_at')->nullable();

            $t->dateTime('reversed_at')->nullable();
            $t->unsignedBigInteger('reversed_by')->nullable();
            $t->string('reversed_by_name')->nullable();
            $t->text('reverse_reason')->nullable();

            $t->dateTime('cancelled_at')->nullable();
            $t->unsignedBigInteger('cancelled_by')->nullable();
            $t->string('cancelled_by_name')->nullable();
            $t->timestamps();
        });

        Schema::create('payout_statement_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('statement_id')->index();
            $t->unsignedBigInteger('ledger_entry_id')->index();
            $t->unsignedTinyInteger('active')->nullable();            // 1 = the line is in a live (draft or paid) statement; NULL = freed
            $t->unsignedBigInteger('booking_number')->nullable()->index();
            $t->string('type', 20);                                   // payment | refund
            $t->date('checkout_date')->nullable();
            $t->string('property_name')->nullable();
            $t->bigInteger('gross_paisa');
            $t->bigInteger('vat_paisa')->default(0);
            $t->bigInteger('platform_service_paisa')->default(0);
            $t->bigInteger('commission_paisa')->default(0);
            $t->bigInteger('vendor_paisa');
            $t->timestamps();

            $t->unique(['ledger_entry_id', 'active']);                // a line is in ONE live statement at most
        });

        Schema::create('payout_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('statement_id')->index();
            $t->string('action', 30);                                 // created | booking_removed | cancelled | paid | reversed | emailed | email_failed
            $t->text('note')->nullable();
            $t->unsignedBigInteger('by_id')->nullable();
            $t->string('by_name')->nullable();
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_events');
        Schema::dropIfExists('payout_statement_lines');
        Schema::dropIfExists('payout_statements');
    }
};
