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
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
            $table->string('name');
            $table->decimal('lat');
            $table->decimal('long');

            $table->string('external_id')->nullable();
            $table->string('external_source')->nullable();
            $table->text('full_address')->nullable();

            $table->string('zip_code')->nullable();
            $table->string('description')->nullable();
            $table->string('nearest_police')->nullable();
            $table->string('nearest_hospital')->nullable();
            $table->string('nearest_fire')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
