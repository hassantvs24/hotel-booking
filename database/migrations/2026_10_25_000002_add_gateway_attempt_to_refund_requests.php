<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A refund that failed can be tried again. Every try is a new request to SSLCommerz and needs its own transaction id,
     * so the id is built from this counter (1 = the first try).
     */
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $t) {
            $t->unsignedSmallInteger('gateway_attempt')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $t) {
            $t->dropColumn('gateway_attempt');
        });
    }
};
