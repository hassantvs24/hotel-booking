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
        Schema::create('property_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('property_id')->nullable()->constrained()->onDelete('set null');
            $table->string('unique_request_number')->unique();
            $table->string('name');
            $table->string('owner_email');
            $table->string('contact_number');
            $table->text('reason');
            $table->string('property_title');
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zipcode')->nullable();
            $table->decimal('lowest_price', 10, 2)->nullable();
            $table->decimal('highest_price', 10, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('admin_message')
                ->nullable()
                ->comment('Admin message for the property owner request approval or rejection');

            $table->json('draft_data')
                ->nullable()
                ->comment('Wizard step data stored as JSON');
            $table->unsignedTinyInteger('draft_step')
                ->default(1)
                ->comment('Last completed wizard step (1-8)');
            $table->boolean('is_draft')
                ->default(true)
                ->comment('True while owner is still filling in the wizard');

            $table->decimal('latitude', 10, 7)
                ->nullable();

            $table->decimal('longitude', 10, 7)
                ->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_requests');
    }
};
