<?php

namespace App\Services\Room;

use App\Models\Media;
use App\Models\Room;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A copy of a room: the same details, facilities and photos, under a new name.
 *
 * The photo FILES are copied too, not shared. Deleting a room deletes its files, so two rooms pointing at
 * the same file would lose the picture together.
 */
class RoomDuplicator
{
    public function copy(Room $source, string $name): Room
    {
        $copy = $source->replicate(['photo', 'booked_date', 'booked_off_date']);
        $copy->name   = $name;
        $copy->status = 'Available';
        $copy->save();

        $copy->facilities()->sync($source->facilities()->pluck('facility_subs.id')->all());

        if ($primary = $source->primaryImage()->first()) {
            $this->copyMedia($primary, $copy, 'room_image', 0);
        }

        foreach ($source->images()->get() as $i => $photo) {
            $this->copyMedia($photo, $copy, 'room_gallery_image', $i + 1);
        }

        return $copy;
    }

    /** "Deluxe (copy)", then "Deluxe (copy 2)" ... the first one the property does not already have */
    public function copyName(Room $source): string
    {
        $base = preg_replace('/\s*\(copy(?: \d+)?\)$/i', '', $source->name);

        $taken = Room::where('property_id', $source->property_id)->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();

        $candidate = "{$base} (copy)";
        for ($n = 2; in_array(mb_strtolower($candidate), $taken, true); $n++) {
            $candidate = "{$base} (copy {$n})";
        }

        return $candidate;
    }

    private function copyMedia(Media $media, Room $room, string $role, int $order): void
    {
        $disk = Storage::disk('public');
        $from = trim($media->path, '/') . '/' . $media->name;

        // A photo whose file has gone missing is skipped rather than copied as a broken picture
        if (!$disk->exists($from)) {
            return;
        }

        $extension = pathinfo($media->name, PATHINFO_EXTENSION) ?: 'jpg';
        $newName   = sprintf('%s-%s.%s', now()->format('Y-m-d-His'), Str::lower(Str::random(20)), $extension);

        $disk->copy($from, trim($media->path, '/') . '/' . $newName);

        $room->{$role === 'room_image' ? 'primaryImage' : 'images'}()->create([
            'name'       => $newName,
            'type'       => $media->type,
            'path'       => $media->path,
            'size'       => $media->size,
            'mime'       => $media->mime,
            'media_role' => $role,
            'sort_order' => $order,
        ]);
    }
}
