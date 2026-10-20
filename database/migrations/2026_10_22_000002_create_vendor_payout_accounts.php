<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where each vendor wants to be paid (a bank account or a mobile wallet). One account per vendor.
     * Every change is written to the history table, so a swapped account number can always be traced.
     */
    public function up(): void
    {
        Schema::create('vendor_payout_accounts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->unique();
            $t->string('method', 12);                                 // bank | bkash | nagad | rocket
            $t->string('holder_name');
            $t->string('account_number', 40);                         // account number, or the wallet's mobile number
            $t->string('bank_name')->nullable();
            $t->string('branch_name')->nullable();
            $t->string('routing_number', 20)->nullable();
            $t->timestamps();
        });

        Schema::create('vendor_payout_account_changes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->unsignedBigInteger('changed_by')->nullable();
            $t->string('changed_by_name')->nullable();
            $t->json('old_values')->nullable();
            $t->json('new_values');
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payout_account_changes');
        Schema::dropIfExists('vendor_payout_accounts');
    }
};
