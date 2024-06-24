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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->date('checkin');
            $table->date('checkout');
            $table->integer('adult')->default(1);
            $table->integer('children')->default(0);
            $table->integer('room')->default(1);
            $table->double('price')->default(0)->comment('Offer price');
            $table->double('final_price')->default(0);
            $table->string('notes')->nullable();
            $table->enum('offer_from', ['Generale', 'Request'])->default('Generale');
            $table->enum('status', ['Pending', 'Negotiate', 'Canceled', 'Accepted'])->default('Pending');
            $table->foreignId('rooms_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
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
        Schema::dropIfExists('offers');
    }
};
