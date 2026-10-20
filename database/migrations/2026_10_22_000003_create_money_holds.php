<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The admin holds back the vendor's money for one booking (a dispute, a doubt) until they lift the hold.
     * A booking is held while its row is here. Lifting a hold deletes the row; the payout events keep the history.
     */
    public function up(): void
    {
        Schema::create('money_holds', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_number')->unique();
            $t->text('note');
            $t->unsignedBigInteger('held_by')->nullable();
            $t->string('held_by_name')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('money_holds');
    }
};
