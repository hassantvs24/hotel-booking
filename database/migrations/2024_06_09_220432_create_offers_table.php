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
        $allowedOfferFor = config('site_configs.allowed_offer_for');
        $allowedOfferStatus = config('site_configs.allowed_offer_status');

        Schema::create('offers', function (Blueprint $table) use ($allowedOfferFor, $allowedOfferStatus) {
            $table->id();
            $table->date('checkin');
            $table->date('checkout');
            $table->integer('adult')->default(1);
            $table->integer('children')->default(0);
            $table->integer('room')->default(1);
            $table->integer('price')->default(0)->comment('Offer price');
            $table->integer('final_price')->default(0);
            $table->string('notes')->nullable();
            $table->enum('offer_from', $allowedOfferFor)->default('General');
            $table->enum('status', $allowedOfferStatus)->default('Pending');

            $table->foreignId('room_id')
                ->constrained()
                ->onDelete('cascade')
                ->onUpdate('No Action');

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade')
                ->onUpdate('No Action');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        Schema::dropIfExists('offers');
    }
};
