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
        Schema::create('room_facility_setups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_subs_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
            $table->foreignId('rooms_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_facility_setups');
    }
};
