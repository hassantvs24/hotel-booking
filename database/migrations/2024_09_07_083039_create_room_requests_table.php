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
        Schema::create('room_requests', function (Blueprint $table) {
            $table->id();
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('adult')->default(1);
            $table->integer('children')->default(0);
            $table->integer('room')->default(1);
            $table->enum('status', ['Pending', 'Done', 'TimeOut'])->default('Pending');

            $table->foreignId('room_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null')
                ->onUpdate('No Action');

            $table->foreignId('property_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null')
                ->onUpdate('No Action');

            $table->foreignId('user_id')
                ->nullable()
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
    public function down(): void
    {
        Schema::dropIfExists('room_requests');
    }
};
