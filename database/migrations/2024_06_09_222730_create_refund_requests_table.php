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
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number')->unique();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->restrictOnDelete();

            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('original_amount', 15, 2);
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->decimal('cancellation_fee', 15, 2)->default(0);

            $table->string('currency', 3)->default('BDT');

            $table->enum('status', [
                'requested',
                'approved',
                'processing',
                'completed',
                'rejected',
                'failed',
                'cancelled',
            ])->default('requested');

            $table->string('reason_code');
            $table->text('reason_details')->nullable();
            $table->text('admin_note')->nullable();
            $table->text('failure_reason')->nullable();

            $table->string('gateway_refund_id')->nullable()->unique();
            $table->string('gateway_status')->nullable();
            $table->string('idempotency_key')->unique();

            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->json('gateway_response')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
