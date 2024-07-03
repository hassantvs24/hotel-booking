<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up() : void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->float('rating')->nullable();
            $table->foreignId('review_category_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
            $table->foreignId('property_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        Schema::dropIfExists('reviews');
    }
};
