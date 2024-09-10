<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $allowedStatus = config('site_configs.allowed_booking_status');

        Schema::create('booking_requests', function (Blueprint $table) use ($allowedStatus) {
            $table->id();
            $table->string('search_name')->comment('Place name here by user');
            $table->date('checkin');
            $table->date('checkout');
            $table->integer('adult')->default(1);
            $table->integer('children')->default(0);
            $table->integer('room')->default(1);

            $table->enum('status', $allowedStatus)
                ->default('Pending');
            $table->timestamp('request_expiration_time')->nullable();
            $table->foreignId('place_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null')
                ->onUpdate('No Action');

            $table->foreignId('user_id')
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
        Schema::dropIfExists('booking_requests');
    }
};
