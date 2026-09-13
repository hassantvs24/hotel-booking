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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->index();
            $table->foreign('booking_id')
                ->references('booking_number') // Ensure booking_number is compatible
                ->on('bookings')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->decimal('amount', 15, 2);
            $table->text('meta')->nullable();
            $table->string('payment_method')
            ->nullable();
            $table->string('transaction_reference')->nullable()->comment('Reference ID from payment gateway');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])
                ->default('pending');
            $table->string('notes')->nullable()->comment('Additional details about the transaction');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
