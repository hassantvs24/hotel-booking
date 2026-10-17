<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner verification: the owner's NID (once per person) and the trade licence (once per property application).
 * The documents live on the private disk and are only ever read through the admin API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('nid_number');                       // encrypted by the model
            $table->char('nid_hash', 64)->index();             // lets the admin see when one NID sits on two accounts
            $table->string('nid_front_path');
            $table->string('nid_back_path');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('property_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('license_number', 100);
            $table->string('file_path');
            $table->date('expires_on')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('verification_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('property_request_id')->nullable()->index();
            $table->string('action', 40);                     // viewed_details, viewed_nid_front, verified_nid ...
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('property_requests', function (Blueprint $table) {
            // true for applications that went through the Verification step; older ones are not held up by it
            $table->boolean('verification_required')->default(false)->after('is_draft');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('properties', fn (Blueprint $t) => $t->dropColumn('verified_at'));
        Schema::table('property_requests', fn (Blueprint $t) => $t->dropColumn('verification_required'));
        Schema::dropIfExists('verification_access_logs');
        Schema::dropIfExists('property_licenses');
        Schema::dropIfExists('owner_identities');
    }
};
