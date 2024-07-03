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
        Schema::create('offer_applies', function (Blueprint $table) {//This is initiated by property owner
            $table->id();
            $table->integer('owner_price')->default(0);
            $table->integer('customer_price')->default(0);
            $table->string('notes')->nullable();

            $table->foreignId('offer_id')
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
        Schema::dropIfExists('offer_applies');
    }
};
