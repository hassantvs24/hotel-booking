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
        Schema::create('make_booking_offers', function (Blueprint $table) {
            $table->id();
            $table->string('notes')->comment('Offer Notes');
            $table->enum('status', ['Pending', 'Accepted'])->default('Pending');
            $table->double('price')->default(0)->comment('Offer price');
            $table->foreignId('rooms_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
            $table->foreignId('booking_requests_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
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
