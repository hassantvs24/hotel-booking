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

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('booking_number')->unique();
            $table->date('checkin');
            $table->date('checkout');
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('adult')->default(1);
            $table->integer('children')->default(0);
            $table->integer('rooms')->default(1);
            $table->string('reference')->nullable();
            $table->string('notes')->comment('Booking notes')->nullable();
            $table->enum('status', ['pending', 'reserved', 'approved'])
                ->default('Pending');
            $table->foreignId('room_id')
                ->constrained()
                ->onDelete('cascade')
                ->onUpdate('No Action');

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade')
                ->onUpdate('No Action');

            $table->enum('payment_status', ['pending', 'paid', 'failed'])
                ->default('pending');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
