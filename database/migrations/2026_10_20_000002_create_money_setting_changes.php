<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who changed which money setting (commission, wait days, payout day...), from what to what, and when.
     * Rows are only ever added.
     */
    public function up(): void
    {
        Schema::create('money_setting_changes', function (Blueprint $t) {
            $t->id();
            $t->string('setting', 60)->index();                  // e.g. "commission_percent", or "property_commission"
            $t->unsignedBigInteger('property_id')->nullable()->index();
            $t->string('property_name')->nullable();
            $t->string('old_value')->nullable();
            $t->string('new_value')->nullable();
            $t->unsignedBigInteger('changed_by')->nullable();
            $t->string('changed_by_name')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('money_setting_changes');
    }
};
