<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->unique()
                ->constrained('bookings')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('positive_comment')->nullable();
            $table->text('negative_comment')->nullable();
            $table->decimal('overall_rating', 3, 1);

            $table->enum('status', ['published', 'hidden'])->default('published');

            $table->text('admin_reply')->nullable();
            $table->foreignId('admin_replied_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('admin_replied_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_submissions');
    }
};
