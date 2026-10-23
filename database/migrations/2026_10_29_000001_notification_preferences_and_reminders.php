<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - What each person wants from each kind of notification (a pop-up, a sound, an email).
     * - An offer that is about to run out is reminded about once: this notes that it was.
     */
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::table('room_requests', function (Blueprint $table) {
            $table->timestamp('expiry_reminded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('room_requests', function (Blueprint $table) {
            $table->dropColumn('expiry_reminded_at');
        });

        Schema::dropIfExists('notification_preferences');
    }
};
