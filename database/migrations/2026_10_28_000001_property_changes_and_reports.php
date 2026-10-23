<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - A new property name waits for the admin's approval (the old name stays live meanwhile).
     * - Every change an owner or the admin makes in the property settings is written down, so it can be looked at and undone.
     * - Guests can report a property; the admin can hide one photo or the whole property (and show it again).
     */
    public function up(): void
    {
        Schema::create('property_name_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('old_name');
            $table->string('new_name');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
        });

        Schema::create('property_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('role', 20)->default('owner');          // owner, admin or system
            $table->string('section', 30);                        // basic, seo, facilities, rules, photos, moderation
            $table->string('field', 60);
            $table->string('label', 120);
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->string('kind', 12)->default('text');          // text, list, photo, event (an event is only a note: it cannot be undone)
            $table->json('meta')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->foreignId('reverted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['property_id', 'created_at']);
        });

        Schema::create('property_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 30);                         // wrong_photos, misleading_info, other
            $table->text('details')->nullable();
            $table->unsignedBigInteger('media_id')->nullable();   // the photo the guest pointed at
            $table->enum('status', ['open', 'actioned', 'dismissed'])->default('open');
            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->timestamp('hidden_by_admin_at')->nullable();
            $table->text('hidden_reason')->nullable();
        });

        Schema::table('media', function (Blueprint $table) {
            $table->timestamp('hidden_at')->nullable();
            $table->text('hidden_reason')->nullable();
            $table->string('hidden_from_role', 40)->nullable();
        });

        // a hidden photo gets a role of its own, so every place that shows photos by their role simply stops showing it
        $type = DB::selectOne("SHOW COLUMNS FROM media WHERE Field = 'media_role'")->Type;
        preg_match_all("/'([^']+)'/", $type, $m);

        if (!in_array('hidden_image', $m[1], true)) {
            $list = implode(',', array_map(fn ($r) => "'" . str_replace("'", "''", $r) . "'", array_merge($m[1], ['hidden_image'])));

            DB::statement("ALTER TABLE media MODIFY media_role ENUM({$list}) NOT NULL DEFAULT 'other' COMMENT 'Media Usage Type'");
        }
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['hidden_at', 'hidden_reason', 'hidden_from_role']);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['hidden_by_admin_at', 'hidden_reason']);
        });

        Schema::dropIfExists('property_reports');
        Schema::dropIfExists('property_change_logs');
        Schema::dropIfExists('property_name_requests');
        // (the media role "hidden_image" stays: rows that use it would be cut off)
    }
};
