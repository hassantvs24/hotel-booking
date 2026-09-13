<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE room_requests MODIFY COLUMN status ENUM('Pending', 'Approved', 'Counter', 'Declined', 'Done', 'Timeout') NOT NULL DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE room_requests MODIFY COLUMN status ENUM('Pending', 'Done', 'Timeout', 'Approved', 'Counter') NOT NULL DEFAULT 'Pending'");
    }
};
