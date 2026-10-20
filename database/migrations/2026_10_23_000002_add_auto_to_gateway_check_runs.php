<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tells a run that the server's scheduler started by itself from one the admin started with "Check now".
     * Only the first kind proves the scheduler is alive, so only it counts for the "automatic check is not running" warning.
     */
    public function up(): void
    {
        Schema::table('gateway_check_runs', function (Blueprint $t) {
            $t->boolean('auto')->default(false)->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('gateway_check_runs', function (Blueprint $t) {
            $t->dropColumn('auto');
        });
    }
};
