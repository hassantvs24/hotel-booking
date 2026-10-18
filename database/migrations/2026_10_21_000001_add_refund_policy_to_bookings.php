<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The refund rules a guest was shown when the booking was made, saved with the booking.
     *
     * The owner can change the property's refund policy at any time, but that only applies to bookings made afterwards.
     * NULL (every booking made before this column existed) means "no saved copy": those bookings keep following the
     * property's current policy, exactly as before.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $t) {
            if (!Schema::hasColumn('bookings', 'refund_policy')) {
                $t->json('refund_policy')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $t) {
            if (Schema::hasColumn('bookings', 'refund_policy')) {
                $t->dropColumn('refund_policy');
            }
        });
    }
};
