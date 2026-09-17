<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separate from the existing `rating` column, which is a
     * manually-editable admin field (Property Settings > Basic Settings)
     * — these two columns are the real, review-derived aggregate.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('reviews_avg_rating', 3, 1)->nullable()->after('rating');
            $table->unsignedInteger('reviews_count')->default(0)->after('reviews_avg_rating');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['reviews_avg_rating', 'reviews_count']);
        });
    }
};
