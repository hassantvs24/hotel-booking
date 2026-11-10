<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An extra bed has a price. The owner sets it per room (price of ONE bed for ONE night); the guest picks how many beds
 * when booking, so the price is known before payment.
 *
 *  - rooms.extra_bed_price     : NULL = the owner has not set a price, so the bed cannot be booked online (0 = free, on purpose)
 *  - booking_carts.extra_beds  : how many beds the guest chose for this cart row
 *  - bookings.extra_beds       : how many beds were booked
 *  - bookings.extra_bed_amount : the charge for them BEFORE VAT and service charge (already inside subtotal_amount and amount)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->decimal('extra_bed_price', 10, 2)->nullable()->after('extra_bed');
        });

        Schema::table('booking_carts', function (Blueprint $table) {
            $table->unsignedTinyInteger('extra_beds')->default(0)->after('rooms');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedTinyInteger('extra_beds')->default(0);
            $table->decimal('extra_bed_amount', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $t) => $t->dropColumn(['extra_beds', 'extra_bed_amount']));
        Schema::table('booking_carts', fn (Blueprint $t) => $t->dropColumn('extra_beds'));
        Schema::table('rooms', fn (Blueprint $t) => $t->dropColumn('extra_bed_price'));
    }
};
