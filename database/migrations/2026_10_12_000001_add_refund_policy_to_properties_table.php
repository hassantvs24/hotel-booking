<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each property owner sets their own refund tiers. NULL means "never set" and the platform
     * default in config/refund.php is used, so every existing property keeps behaving as before.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->json('refund_policy')->nullable()->after('reviews_count');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('refund_policy');
        });
    }
};
