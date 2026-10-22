<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The full world list: what kind of area each row is, its local-language name, where it came from,
     * and a third level (upazila and the like) under every district.
     */
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('native_name')->nullable()->after('name');
            $table->string('region')->nullable()->after('flag');
        });

        Schema::table('states', function (Blueprint $table) {
            $table->string('type')->nullable()->after('short_name');
            $table->string('native_name')->nullable()->after('name');
            $table->string('external_id')->nullable()->index();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->string('type')->nullable()->after('name');
            $table->string('native_name')->nullable()->after('name');
            $table->string('external_id')->nullable()->index();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->onDelete('cascade')->onUpdate('No Action');
            $table->string('name');
            $table->string('native_name')->nullable();
            $table->string('type')->nullable();
            $table->decimal('lat', 8, 4)->nullable();
            $table->decimal('long', 8, 4)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');

        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['type', 'native_name', 'external_id']);
        });

        Schema::table('states', function (Blueprint $table) {
            $table->dropColumn(['type', 'native_name', 'external_id']);
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['native_name', 'region']);
        });
    }
};
