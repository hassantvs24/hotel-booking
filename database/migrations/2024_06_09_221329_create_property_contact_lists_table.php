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
        Schema::create('property_contact_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Contact Title');
            $table->string('notes')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('properties_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_contact_lists');
    }
};
