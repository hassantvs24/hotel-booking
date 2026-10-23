<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guests are emailed when the property writes to them. This remembers, per conversation, up to which message
     * the guest has been emailed, so a line is never emailed twice and a line that had to wait is not forgotten.
     * Everything written before this change counts as already emailed: nobody gets a mail about an old conversation.
     */
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('guest_emailed_until_id')->nullable();
        });

        DB::statement('update chat_conversations c set guest_emailed_until_id = (select max(m.id) from chat_messages m where m.conversation_id = c.id)');
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropColumn('guest_emailed_until_id');
        });
    }
};
