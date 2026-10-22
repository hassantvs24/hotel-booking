<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A refund is judged on the day the guest ASKED, so the admin may approve it after the check-in day has begun (they were late).
     * Then the admin must confirm that the guest did not stay: this records that they did, and when.
     */
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $t) {
            $t->dateTime('not_stayed_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $t) {
            $t->dropColumn('not_stayed_confirmed_at');
        });
    }
};
