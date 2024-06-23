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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('lat');
            $table->decimal('long');
            $table->string('photo')->nullable();
            $table->string('address')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('total_room')->nullable();
            $table->string('currency')->nullable();
            $table->float('rating')->nullable();
            $table->string('google_review')->nullable();//Google review link
            $table->string('seo_title')->nullable();
            $table->string('seo_meta')->nullable();
            $table->enum('property_class', ['7 Stars', '6 Stars', '5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star', 'Unrated'])->default('Unrated');
            $table->enum('status', ['Pending', 'Published', 'Unpublished'])->default('Pending');
            $table->foreignId('property_categories_id')->nullable()->constrained()->onDelete('set null')->onUpdate('No Action');
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
        Schema::dropIfExists('properties');
    }
};
