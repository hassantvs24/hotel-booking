<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A person looked at a booking whose money was held back (it was deleted, changed by hand, ...) and let it go.
     * The note says why. Rows are only ever added. A release covers one reason: if the booking later changes in a
     * different way, it is held again and needs a new look.
     */
    public function up(): void
    {
        Schema::create('money_reviews', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_number');
            $t->string('reason', 40);
            $t->text('note');
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->string('reviewed_by_name')->nullable();
            $t->timestamps();

            $t->unique(['booking_number', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('money_reviews');
    }
};
