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
        Schema::create('room_request_accepteds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_requests_id')
                ->constrained()
                ->onDelete('cascade')
                ->onUpdate('No Action');

            $table->foreignId('property_id')
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
        Schema::dropIfExists('room_request_accepteds');
    }
};
