<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reconciliation: does our record of the money agree with SSLCommerz's, and with the bank?
     *
     * - gateway_checks: one row for each payment (a booking, or a whole cart) that was asked about at SSLCommerz, with what
     *   we have, what SSLCommerz says, and the verdict. Checking never changes the money records; it only reports.
     * - gateway_check_runs: one row for every automatic run (how many were checked, how many did not agree).
     * - bank_deposits: what the admin saw arrive in the bank from SSLCommerz, for a period, next to what should have come.
     *   A deposit is never edited or deleted; a wrong one is voided with a reason.
     */
    public function up(): void
    {
        Schema::create('gateway_checks', function (Blueprint $t) {
            $t->id();
            $t->string('tran_id', 64)->unique();                      // the id SSLCommerz knows: a booking number, a cart reference, or "refund:12"
            $t->string('kind', 12);                                   // payment | refund
            $t->string('our_status', 16);                             // completed | refunded | unconfirmed
            $t->bigInteger('our_paisa')->default(0);
            $t->string('gateway_status', 30)->nullable();
            $t->bigInteger('gateway_paisa')->nullable();
            $t->bigInteger('gateway_fee_paisa')->nullable();          // what SSLCommerz keeps (amount - store_amount)
            $t->bigInteger('ledger_fee_paisa')->nullable();           // what our money records say it kept
            $t->string('bank_tran_id', 80)->nullable();
            $t->string('gateway_date', 30)->nullable();
            $t->json('bookings')->nullable();                         // the booking numbers behind it, for the screens
            $t->string('result', 24)->index();                        // ok | amount_differs | status_differs | fee_differs | not_at_gateway | paid_unconfirmed | gateway_error
            $t->text('problem')->nullable();
            $t->unsignedInteger('attempts')->default(0);              // how many times in a row SSLCommerz could not be reached
            $t->dateTime('checked_at')->nullable()->index();
            $t->dateTime('subject_at')->nullable();                   // when the payment was made (used to decide how long it is worth re-checking)
            $t->dateTime('resolved_at')->nullable();
            $t->unsignedBigInteger('resolved_by')->nullable();
            $t->string('resolved_by_name')->nullable();
            $t->text('resolved_note')->nullable();
            $t->timestamps();
        });

        Schema::create('gateway_check_runs', function (Blueprint $t) {
            $t->id();
            $t->dateTime('started_at');
            $t->dateTime('finished_at')->nullable();
            $t->unsignedInteger('checked')->default(0);
            $t->unsignedInteger('agreed')->default(0);
            $t->unsignedInteger('problems')->default(0);
            $t->unsignedInteger('errors')->default(0);
        });

        Schema::create('bank_deposits', function (Blueprint $t) {
            $t->id();
            $t->date('period_from');                                  // the payments this deposit is for: made from ...
            $t->date('period_to');                                    // ... to (Dhaka days, both included)
            $t->date('received_on');                                  // the day it reached the bank
            $t->bigInteger('received_paisa');
            $t->bigInteger('expected_paisa');                         // worked out when it was entered: what should have come
            $t->bigInteger('difference_paisa');                       // received - expected
            $t->unsignedInteger('fee_unknown_count')->default(0);     // payments whose SSLCommerz fee we do not know (the expected amount may be a little high)
            $t->string('reference', 120)->nullable();
            $t->text('note')->nullable();
            $t->unsignedBigInteger('entered_by')->nullable();
            $t->string('entered_by_name')->nullable();
            $t->dateTime('voided_at')->nullable();
            $t->unsignedBigInteger('voided_by')->nullable();
            $t->string('voided_by_name')->nullable();
            $t->text('void_reason')->nullable();
            $t->timestamps();
        });

        // how many days between bank checks (7, 15, 30, or any number): changeable on the Money settings page
        if (!DB::table('settings')->where('key', 'bank_check_days')->exists()) {
            DB::table('settings')->insert([
                'key' => 'bank_check_days', 'value' => '7', 'value_type' => 'text', 'group' => 'payout',
                'admin_only' => 1, 'is_deletable' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_deposits');
        Schema::dropIfExists('gateway_check_runs');
        Schema::dropIfExists('gateway_checks');
        DB::table('settings')->where('key', 'bank_check_days')->delete();
    }
};
