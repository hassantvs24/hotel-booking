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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('photo')->nullable();
            $table->integer('room_number')->comment('use if applicable')->nullable();
            $table->integer('room_size')->nullable();//Square meter
            $table->integer('guest_capacity')->nullable();
            $table->boolean('extra_bed')->default(1)->comment('1 means possible');
            $table->integer('total_balcony')->default(0);
            $table->integer('total_window')->default(0);
            $table->double('base_price')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('bed_types_id')->nullable()->constrained()->onDelete('Set Null')->onUpdate('No Action');
            $table->foreignId('room_types_id')->nullable()->constrained()->onDelete('Set Null')->onUpdate('No Action');
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
        Schema::dropIfExists('rooms');
    }
};
