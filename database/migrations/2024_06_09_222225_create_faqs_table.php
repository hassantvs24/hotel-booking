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
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete()
                ->noActionOnUpdate();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('faq_answers', function (Blueprint $table) {
            $table->id();
            $table->text('answer');
            $table->foreignId('faq_id')
                ->constrained()
                ->cascadeOnDelete()
                ->noActionOnUpdate();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
