<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The order of a room's gallery photos (the slider on the room card follows it). Photos without a number
     * keep their old order (by id).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('media', 'sort_order')) {
            Schema::table('media', function (Blueprint $table) {
                $table->unsignedSmallInteger('sort_order')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
