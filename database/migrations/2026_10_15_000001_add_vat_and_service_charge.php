<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * VAT and service charge on room prices.
     *
     * Rates can be set on three levels — the room, its property, and the platform (admin settings). A NULL
     * rate means "use the level above"; 0 means "no tax". `bookings` keeps what was charged at the time
     * (rates and amounts), so changing a rate later never rewrites an old booking. `bookings.amount` stays
     * the total the guest pays, so payments, refunds and emails keep working on it unchanged.
     */
    public function up(): void
    {
        foreach (['rooms', 'properties'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'vat_percent')) {
                    $t->decimal('vat_percent', 5, 2)->nullable();
                }
                if (!Schema::hasColumn($table, 'service_charge_percent')) {
                    $t->decimal('service_charge_percent', 5, 2)->nullable();
                }
            });
        }

        Schema::table('bookings', function (Blueprint $t) {
            foreach (['subtotal_amount', 'service_charge_amount', 'vat_amount'] as $col) {
                if (!Schema::hasColumn('bookings', $col)) {
                    $t->decimal($col, 15, 2)->nullable();
                }
            }
            foreach (['vat_percent', 'service_charge_percent'] as $col) {
                if (!Schema::hasColumn('bookings', $col)) {
                    $t->decimal($col, 5, 2)->nullable();
                }
            }
        });

        // The platform's own rates, editable by the admin (15% VAT and 10% service charge to begin with)
        foreach ([['vat_percent', '15'], ['service_charge_percent', '10']] as [$key, $value]) {
            if (!DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key'          => $key,
                    'value'        => $value,
                    'value_type'   => 'text',
                    'group'        => 'tax',
                    'admin_only'   => 1,
                    'is_deletable' => 0,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $t) {
            $t->dropColumn(['subtotal_amount', 'service_charge_amount', 'vat_amount', 'vat_percent', 'service_charge_percent']);
        });
        foreach (['rooms', 'properties'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['vat_percent', 'service_charge_percent']);
            });
        }
        DB::table('settings')->whereIn('key', ['vat_percent', 'service_charge_percent'])->delete();
    }
};
