<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Set once SSLCommerz gave no readable answer to a refund request: it may already have paid it. From then on "Try again"
     * always sends the SAME transaction id (never a new one), so the guest can never be paid twice. Only the admin, after
     * looking in the SSLCommerz panel, resolves it ("mark refunded").
     */
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $t) {
            $t->boolean('gateway_maybe_paid')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $t) {
            $t->dropColumn('gateway_maybe_paid');
        });
    }
};
