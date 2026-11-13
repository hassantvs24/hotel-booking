<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instant accept for offers.
 *
 * rooms.auto_accept_min_price: the lowest price per night (before VAT and service charge) at which the owner accepts an
 * offer for this room without answering it. NULL = the owner answers every offer (the way it always was).
 *
 * room_requests.auto_accepted: this offer was accepted by that rule, not by the owner. Only a yes/no is kept here: the price
 * itself is the owner's secret and an offer row is sent to the guest as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'auto_accept_min_price')) {
                $table->decimal('auto_accept_min_price', 10, 2)->nullable()->after('extra_bed_price');
            }
        });

        Schema::table('room_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('room_requests', 'auto_accepted')) {
                $table->boolean('auto_accepted')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('room_requests', function (Blueprint $table) {
            if (Schema::hasColumn('room_requests', 'auto_accepted')) {
                $table->dropColumn('auto_accepted');
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasColumn('rooms', 'auto_accept_min_price')) {
                $table->dropColumn('auto_accept_min_price');
            }
        });
    }
};
