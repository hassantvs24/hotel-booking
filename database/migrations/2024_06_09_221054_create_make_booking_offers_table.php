<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $allowedStatus = config('site_configs.allowed_booking_offer_status');

        Schema::create('make_booking_offers', function (Blueprint $table) use ($allowedStatus) {
            $table->id();
            $table->string('notes')->comment('Offer Notes');
            $table->enum('status', $allowedStatus)->default('Pending');
            $table->integer('price')->default(0)->comment('Offer price');

            $table->foreignId('room_id')
                ->constrained()->onDelete('cascade')
                ->onUpdate('No Action');

            $table->foreignId('booking_request_id')
                ->constrained()
                ->onDelete('cascade')
                ->onUpdate('No Action');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('make_booking_offers');
    }
};
