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
        Schema::create('property_rules_setups', function (Blueprint $table) {
            $table->id();
            $table->text('rule_description');
            $table->boolean('is_active')->default(1)->comment('1 means active');
            $table->foreignId('property_rules_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
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
        Schema::dropIfExists('property_rules_setups');
    }
};
