<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The code saves room gallery photos as "room_gallery_image" and the property logo as
     * "property_logo_image", but the media_role column never allowed those two values, so every
     * such upload failed with "Data truncated for column 'media_role'". Only adds values — existing
     * rows are untouched. The list is read from the column itself so nothing already allowed is lost.
     */
    public function up(): void
    {
        $this->setRoles(['room_gallery_image', 'property_logo_image'], add: true);
    }

    public function down(): void
    {
        // Rows that use these values would be cut off if they were removed again, so this is left as is.
    }

    private function setRoles(array $roles, bool $add): void
    {
        $type = DB::selectOne("SHOW COLUMNS FROM media WHERE Field = 'media_role'")->Type;

        preg_match_all("/'([^']+)'/", $type, $m);
        $current = $m[1];
        $wanted  = $add ? array_values(array_unique(array_merge($current, $roles))) : array_values(array_diff($current, $roles));

        if ($wanted === $current) {
            return;
        }

        $list = implode(',', array_map(fn ($r) => "'" . str_replace("'", "''", $r) . "'", $wanted));

        DB::statement("ALTER TABLE media MODIFY media_role ENUM({$list}) NOT NULL DEFAULT 'other' COMMENT 'Media Usage Type'");
    }
};
