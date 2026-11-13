<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An offer can carry extra beds. The guest bargains over the room's price only; the bed is the owner's price for ONE bed
 * and ONE night as it was when the offer was sent, saved here so the owner sees the same figure the guest pays,
 * whatever the owner does to the room's bed price afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('room_requests', 'extra_beds')) {
                $table->unsignedTinyInteger('extra_beds')->default(0)->after('children');
            }
            if (!Schema::hasColumn('room_requests', 'extra_bed_price')) {
                $table->decimal('extra_bed_price', 10, 2)->nullable()->after('extra_beds');
            }
        });
    }

    public function down(): void
    {
        Schema::table('room_requests', function (Blueprint $table) {
            foreach (['extra_bed_price', 'extra_beds'] as $col) {
                if (Schema::hasColumn('room_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
