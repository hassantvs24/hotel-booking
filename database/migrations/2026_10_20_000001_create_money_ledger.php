<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The money ledger: one line for every payment that came in and every refund that went out.
     *
     * - Amounts are whole paisa (integers), so there is never a rounding drift.
     * - A line is written once and never edited or deleted (the model refuses). A mistake is fixed by a new line.
     * - Each line carries its own copy of the vendor, property and booking number, so it still reads
     *   correctly if the booking or property is later changed or removed.
     * - For every line:  gross = vat + platform_service + commission + vendor
     *
     * Settings live in the `settings` table, group "payout". The values seeded here are TEMPORARY until the owner confirms them.
     */
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $t) {
            $t->id();
            $t->string('unique_key')->unique();                       // "payment:txn:12" / "refund:rr:5": the same event is never written twice
            $t->string('type', 20)->index();                          // payment | refund
            $t->unsignedBigInteger('transaction_id')->nullable()->index();
            $t->unsignedBigInteger('refund_request_id')->nullable()->index();
            $t->unsignedBigInteger('booking_id')->nullable()->index();
            $t->unsignedBigInteger('booking_number')->nullable()->index();
            $t->unsignedBigInteger('property_id')->nullable()->index();
            $t->string('property_name')->nullable();
            $t->unsignedBigInteger('vendor_id')->nullable()->index();
            $t->string('vendor_name')->nullable();
            $t->date('checkout_date')->nullable();
            $t->date('occurred_on')->index();                         // the calendar day in Dhaka
            $t->dateTime('occurred_at');

            $t->bigInteger('gross_paisa');                            // money in (+) or out (-)
            $t->bigInteger('base_paisa')->default(0);                 // room price
            $t->bigInteger('service_paisa')->default(0);              // service charge
            $t->bigInteger('vat_paisa')->default(0);                  // VAT / tax, held for the government
            $t->bigInteger('platform_service_paisa')->default(0);     // service charge, when the platform keeps it
            $t->bigInteger('commission_paisa')->default(0);           // the platform's commission
            $t->bigInteger('vendor_paisa')->default(0);               // what the vendor is owed
            $t->bigInteger('gateway_fee_paisa')->nullable();          // what SSLCommerz kept (null = not known)

            $t->decimal('commission_percent', 5, 2)->nullable();      // the rate this line was worked out with
            $t->string('service_charge_to', 10)->nullable();          // vendor | admin
            $t->boolean('is_test')->default(false);                   // paid through the local test confirm, no real money
            $t->json('flags')->nullable();                            // reasons a human should look, e.g. ["amount_mismatch"]
            $t->timestamps();
        });

        Schema::table('properties', function (Blueprint $t) {
            if (!Schema::hasColumn('properties', 'commission_percent')) {
                $t->decimal('commission_percent', 5, 2)->nullable();  // null = use the platform rate
            }
        });

        // Settings. TEMPORARY values: the owner decides the real ones.
        $defaults = [
            ['money_start_date',        now('Asia/Dhaka')->toDateString()],   // nothing before this day is booked in the ledger
            ['commission_percent',      '10'],
            ['service_charge_to',       'vendor'],                            // vendor | admin
            ['payout_frequency',        'weekly'],                            // daily | weekly | biweekly | monthly
            ['payout_weekday',          '1'],                                 // 1 = Monday ... 7 = Sunday
            ['payout_wait_days',        '2'],                                 // days after check-out before money is payable
            ['payout_minimum',          '500'],                               // smaller balances wait for the next payout
            ['payout_settings_confirmed', '0'],                               // 1 once the owner has checked every value
        ];

        foreach ($defaults as [$key, $value]) {
            if (!DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key, 'value' => $value, 'value_type' => 'text', 'group' => 'payout',
                    'admin_only' => 1, 'is_deletable' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');

        Schema::table('properties', function (Blueprint $t) {
            if (Schema::hasColumn('properties', 'commission_percent')) {
                $t->dropColumn('commission_percent');
            }
        });

        DB::table('settings')->where('group', 'payout')->delete();
    }
};
