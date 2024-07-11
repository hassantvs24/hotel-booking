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
        $settingValueType = config('site.settings.valueType');

        Schema::create('settings', function (Blueprint $table) use ($settingValueType) {
            $table->id();
            $table->string('key')->unique()->comment('will used to call purposes');
            $table->text('value');
            $table->json('options')->nullable();
            $table->enum('value_type', $settingValueType)->default($settingValueType[0]);
            $table->string('group')->default('default');
            $table->boolean('admin_only')->default(false);
            $table->boolean('is_deletable')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        Schema::dropIfExists('settings');
    }
};
