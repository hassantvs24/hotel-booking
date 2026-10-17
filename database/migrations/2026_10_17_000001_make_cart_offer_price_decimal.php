<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A guest can type any price in the offer popup (BDT 1,500.25 a night). The cart kept offers in a whole-number
     * column, so the price was rounded there and the owner got a different amount than the guest saw.
     */
    public function up(): void
    {
        Schema::table('booking_carts', function (Blueprint $table) {
            $table->decimal('offer_price', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('booking_carts', function (Blueprint $table) {
            $table->integer('offer_price')->default(0)->change();
        });
    }
};
