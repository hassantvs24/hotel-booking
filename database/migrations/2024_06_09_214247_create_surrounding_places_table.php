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
        Schema::create('surrounding_places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('lat');
            $table->decimal('long');
            $table->string('notes')->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('places_id')->nullable()->constrained()->onDelete('No Action')->onUpdate('No Action');
            $table->foreignId('surroundings_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surrounding_places');
    }
};
