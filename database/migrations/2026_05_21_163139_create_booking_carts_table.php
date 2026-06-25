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
        Schema::create('booking_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();

            $table->date('check_in');
            $table->date('check_out');

            $table->unsignedInteger('adult')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->unsignedInteger('rooms')->default(1);

            $table->boolean('is_bid')->default(false);
            $table->integer('offer_price')->default(0);
            $table->text('bid_message')->nullable();

            $table->decimal('price', 15, 2)->default(0)->comment('Price locked at add-to-cart time');
            $table->timestamp('expires_at')->nullable()->comment('+15 min from add time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_carts');
    }
};
