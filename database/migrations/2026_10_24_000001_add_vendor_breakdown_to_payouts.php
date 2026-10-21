<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The VAT and the service charge belong to the vendor, who is shown them. A statement line now also keeps the room price,
     * the service charge and the commission rate it was worked out with, so the vendor can see
     * "booking - commission (10%) = your share", with the VAT and the service charge named inside the share.
     */
    public function up(): void
    {
        Schema::table('payout_statement_lines', function (Blueprint $t) {
            $t->bigInteger('base_paisa')->default(0)->after('gross_paisa');
            $t->bigInteger('service_paisa')->default(0)->after('base_paisa');
            $t->decimal('commission_percent', 5, 2)->nullable()->after('commission_paisa');
        });

        Schema::table('payout_statements', function (Blueprint $t) {
            $t->bigInteger('base_paisa')->default(0)->after('gross_paisa');
            $t->bigInteger('service_paisa')->default(0)->after('base_paisa');
        });
    }

    public function down(): void
    {
        Schema::table('payout_statements', function (Blueprint $t) {
            $t->dropColumn(['base_paisa', 'service_paisa']);
        });

        Schema::table('payout_statement_lines', function (Blueprint $t) {
            $t->dropColumn(['base_paisa', 'service_paisa', 'commission_percent']);
        });
    }
};
