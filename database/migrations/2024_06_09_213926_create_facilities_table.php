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
        $allowedFacilityFor = config('site_configs.allowed_facility_for', ['Room', 'Property']);

        Schema::create('facilities', function (Blueprint $table) use ($allowedFacilityFor) {
            $table->id();
            $table->string('name');
            $table->string('notes')->nullable();
            $table->string('facility_type')->comment('Add if applicable. Example: Property, Place, Room etc')->nullable();
            $table->enum('facility_for', $allowedFacilityFor)->default('Property');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
