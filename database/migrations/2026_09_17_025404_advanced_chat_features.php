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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')
                ->unique()
                ->constrained('bookings')
                ->cascadeOnDelete();
            $table->foreignId('property_id')
                ->constrained('properties')
                ->cascadeOnDelete();
            $table->foreignId('customer_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->enum('category', [
                'checkin_checkout',
                'room_request',
                'parking',
                'airport_transfer',
                'payment',
                'cancellation_refund',
                'special_request',
                'other',
            ])->default('other');
            $table->string('subject', 150)->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('chat_conversations')
                ->cascadeOnDelete();
            $table->foreignId('sender_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('receiver_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('message')->nullable();
            $table->enum('type', ['text', 'system'])->default('text');
            $table->boolean('is_internal')->default(false)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('chat_message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')
                ->constrained('chat_messages')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('read_at');
        });

        Schema::create('chat_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')
                ->constrained('chat_messages')
                ->cascadeOnDelete();
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('chat_special_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('chat_conversations')
                ->cascadeOnDelete();
            $table->foreignId('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('responded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->enum('request_type', [
                'early_checkin',
                'late_checkout',
                'airport_pickup',
                'extra_bed',
                'quiet_room',
                'parking',
                'accessibility',
                'special_occasion',
                'other',
            ]);
            $table->text('details')->nullable();
            $table->enum('status', [
                'pending',
                'accepted',
                'declined',
            ])->default('pending');
            $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_special_requests');
        Schema::dropIfExists('chat_attachments');
        Schema::dropIfExists('chat_message_reads');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
