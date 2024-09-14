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
        $allowedPropertyClass = config('site_configs.allowed_property_class');
        $allowedPropertyStatus = config('site_configs.allowed_property_status');

        Schema::create('properties', function (Blueprint $table) use ($allowedPropertyClass, $allowedPropertyStatus) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable(); //description
            $table->decimal('lat')->nullable();
            $table->decimal('long')->nullable();
            $table->string('address')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('total_room')->nullable();
            $table->string('currency')->nullable();
            $table->float('rating')->nullable();
            $table->string('google_review')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_meta')->nullable();
            $table->enum('property_class', $allowedPropertyClass)
                ->default('Unrated');
            $table->enum('status', $allowedPropertyStatus)
                ->default('Pending');
            $table->time('check_in_time')->nullable(); // Added check-in time
            $table->time('check_out_time')->nullable(); // Added check-out time
            $table->string('phone_number')->nullable(); // Added phone number

            $table->foreignId('property_category_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null')
                ->onUpdate('No Action');

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
        Schema::dropIfExists('properties');
    }
};
