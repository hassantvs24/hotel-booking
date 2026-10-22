<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every booking records WHO WILL STAY: a name and a NID (or, for a foreign guest, a passport number). The number is kept
     * encrypted. The same ID is also remembered on the guest's own profile so it is asked for only once.
     * The profile's `nid` and `passport` columns were plain text: they are made roomy and encrypted too.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $t) {
            $t->string('main_guest_name', 150)->nullable();
            $t->string('main_guest_id_type', 10)->nullable();   // nid | passport
            $t->text('main_guest_id_number')->nullable();       // encrypted
        });

        Schema::table('user_profiles', function (Blueprint $t) {
            $t->text('nid')->nullable()->change();
            $t->text('passport')->nullable()->change();
        });

        foreach (DB::table('user_profiles')->get(['id', 'nid', 'passport']) as $p) {
            $set = [];
            foreach (['nid', 'passport'] as $col) {
                if ($p->{$col} !== null && $p->{$col} !== '') {
                    $set[$col] = Crypt::encryptString($p->{$col});
                }
            }
            if ($set) {
                DB::table('user_profiles')->where('id', $p->id)->update($set);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('user_profiles')->get(['id', 'nid', 'passport']) as $p) {
            $set = [];
            foreach (['nid', 'passport'] as $col) {
                if ($p->{$col} !== null && $p->{$col} !== '') {
                    try {
                        $set[$col] = substr(Crypt::decryptString($p->{$col}), 0, 255);
                    } catch (\Throwable $e) {
                        // already plain
                    }
                }
            }
            if ($set) {
                DB::table('user_profiles')->where('id', $p->id)->update($set);
            }
        }

        Schema::table('user_profiles', function (Blueprint $t) {
            $t->string('nid')->nullable()->change();
            $t->string('passport')->nullable()->change();
        });

        Schema::table('bookings', function (Blueprint $t) {
            $t->dropColumn(['main_guest_name', 'main_guest_id_type', 'main_guest_id_number']);
        });
    }
};
