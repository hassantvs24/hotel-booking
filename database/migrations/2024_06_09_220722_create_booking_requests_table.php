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
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->string('search_name')->comment('Place name here by user');
            $table->date('checkin');
            $table->date('checkout');
            $table->integer('adult')->default(1);
            $table->integer('children')->default(0);
            $table->integer('room')->default(1);
            $table->enum('status', ['Pending', 'Done', 'Timeout'])->default('Pending');
            $table->foreignId('places_id')->nullable()->constrained()->onDelete('set null')->onUpdate('No Action');
            $table->foreignId('users_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
    }
};
