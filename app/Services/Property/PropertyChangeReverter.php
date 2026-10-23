<?php

namespace App\Services\Property;

use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\PropertyChangeLog;
use App\Models\PropertyRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * The admin undoes a change from the log: the field goes back to what it was.
 * Only when nothing has changed it since (the value is still the one this entry set), so one undo never wipes a later change.
 */
class PropertyChangeReverter
{
    private const TEXT_FIELDS = [
        'name', 'description', 'address', 'zip_code', 'lat', 'long', 'phone_number', 'email', 'website', 'property_class',
        'property_category_id', 'check_in_time', 'check_out_time', 'seo_title', 'seo_meta', 'status',
    ];

    public function __construct(private readonly PropertyChangeLogger $log) {}

    /** Why this entry cannot be undone, or null when it can */
    public function why(PropertyChangeLog $entry): ?string
    {
        if ($entry->reverted_at) {
            return 'It was undone already.';
        }

        if ($entry->kind === 'event') {
            return 'This is a note, not a change.';
        }

        $property = Property::find($entry->property_id);

        if (!$property) {
            return 'The property no longer exists.';
        }

        return match ($entry->kind) {
            'text'  => $this->whyText($entry, $property),
            'list'  => $this->whyList($entry, $property),
            'photo' => $this->whyPhoto($entry, $property),
            default => 'This cannot be undone.',
        };
    }

    public function revert(PropertyChangeLog $entry, User $admin): void
    {
        DB::transaction(function () use ($entry, $admin) {
            $entry = PropertyChangeLog::lockForUpdate()->findOrFail($entry->id);

            if ($why = $this->why($entry)) {
                throw ValidationException::withMessages(['log' => [$why]]);
            }

            $property = Property::findOrFail($entry->property_id);

            match ($entry->kind) {
                'text'  => $this->undoText($entry, $property),
                'list'  => $this->undoList($entry, $property),
                'photo' => $this->undoPhoto($entry, $property, $admin),
            };

            $entry->update(['reverted_at' => now(), 'reverted_by' => $admin->id]);

            $this->log->record($property, $admin, $entry->section, $entry->field, 'Undone: ' . $entry->label, $entry->new_value, $entry->old_value, 'event', ['reverted_log_id' => $entry->id]);
        });
    }

    // ── Text ────────────────────────────────────────────────

    private function whyText(PropertyChangeLog $e, Property $p): ?string
    {
        if (!in_array($e->field, self::TEXT_FIELDS, true)) {
            return 'This cannot be undone.';
        }

        return $this->log->plain($this->current($e->field, $p)) === $this->log->plain($e->new_value)
            ? null
            : 'It has been changed again since, so undoing this one could wipe the newer change.';
    }

    /** The value now, in the same form the log stored it */
    private function current(string $field, Property $p): mixed
    {
        return match ($field) {
            'address'              => $p->addressParts()['address'],
            'property_category_id' => $p->property_category_id ? ($p->propertyCategory?->name ?? $p->property_category_id) : null,
            default                => $p->{$field},
        };
    }

    private function undoText(PropertyChangeLog $e, Property $p): void
    {
        $old = $e->old_value;

        if ($e->field === 'address') {
            $parts = $p->addressParts();
            $parts['address'] = $old;
            $p->update(['address' => $parts]);

            return;
        }

        if ($e->field === 'property_category_id') {
            $p->update(['property_category_id' => $old === null ? null : (PropertyCategory::where('name', $old)->value('id') ?? (is_numeric($old) ? (int) $old : null))]);

            return;
        }

        if (in_array($e->field, ['check_in_time', 'check_out_time'], true) && $old !== null) {
            $old .= ':00';
        }

        $p->update([$e->field => $old]);
    }

    // ── Lists (facilities, house rules) ─────────────────────

    private function whyList(PropertyChangeLog $e, Property $p): ?string
    {
        $after = $e->meta['after'] ?? null;

        if ($after === null) {
            return 'This cannot be undone.';
        }

        return $this->currentList($e->field, $p) == $after ? null : 'It has been changed again since, so undoing this one could wipe the newer change.';
    }

    private function currentList(string $field, Property $p): array
    {
        if ($field === 'facilities') {
            return $p->facilities()->pluck('facility_subs.id')->map(fn ($i) => (int) $i)->sort()->values()->all();
        }

        return $p->rules()->get(['property_rule_id', 'rule_description', 'is_active'])
            ->map(fn ($r) => ['property_rule_id' => (int) $r->property_rule_id, 'description' => (string) $r->rule_description, 'is_active' => (bool) $r->is_active])
            ->sortBy('property_rule_id')->values()->all();
    }

    private function undoList(PropertyChangeLog $e, Property $p): void
    {
        $before = $e->meta['before'] ?? [];

        if ($e->field === 'facilities') {
            $p->facilities()->sync($before);

            return;
        }

        $ids = [];

        foreach ($before as $r) {
            if (!PropertyRule::whereKey($r['property_rule_id'])->exists()) {
                continue;
            }

            $ids[] = $r['property_rule_id'];
            $p->rules()->updateOrCreate(['property_rule_id' => $r['property_rule_id']], ['rule_description' => $r['description'], 'is_active' => $r['is_active'] ? 1 : 0, 'property_id' => $p->id]);
        }

        $p->rules()->whereNotIn('property_rule_id', $ids ?: [0])->delete();
    }

    // ── Photos ──────────────────────────────────────────────

    private function whyPhoto(PropertyChangeLog $e, Property $p): ?string
    {
        $m = $e->meta ?? [];

        if ($e->old_value !== null && $e->new_value === null) {
            // a photo that was removed: it can come back while its file is still kept
            if (!$this->fileExists($m['path'] ?? 'properties', $m['name'] ?? '')) {
                return 'The removed photo is no longer kept.';
            }

            if ($m['role'] === 'property_gallery_image' && $p->images()->count() >= 12) {
                return 'The gallery is full (12 photos).';
            }

            return null;
        }

        // a photo that was added: undoing it removes it again, if it is still there
        return Media::whereKey($m['media_id'] ?? 0)->where('media_type', Property::class)->where('media_id', $p->id)->where('media_role', $m['role'] ?? '')->exists() ? null : 'That photo is not on the property any more.';
    }

    private function undoPhoto(PropertyChangeLog $e, Property $p, User $admin): void
    {
        $m = $e->meta;

        if ($e->old_value !== null && $e->new_value === null) {
            // bring it back; a main photo or logo that is there now is put aside (and written down) first
            if ($m['role'] !== 'property_gallery_image') {
                $current = Media::where('media_type', Property::class)->where('media_id', $p->id)->where('media_role', $m['role'])->first();

                if ($current) {
                    $this->log->record($p, $admin, 'photos', $m['role'] === 'property_image' ? 'main_photo' : 'logo', $m['role'] === 'property_image' ? 'Main photo removed' : 'Logo removed', $current->name, null, 'photo', $this->meta($current));
                    $current->delete();
                }
            }

            $p->media()->create([
                'name' => $m['name'], 'path' => $m['path'], 'type' => $m['type'] ?? 'image', 'size' => $m['size'] ?? null, 'mime' => $m['mime'] ?? null,
                'media_role' => $m['role'], 'sort_order' => $m['sort_order'] ?? 0,
            ]);

            return;
        }

        // an added photo is taken off again: the file stays kept for a while, like any removed photo
        $added = Media::whereKey($m['media_id'])->where('media_type', Property::class)->where('media_id', $p->id)->first();

        if ($added) {
            $this->log->record($p, $admin, 'photos', $e->field, 'Photo removed', $added->name, null, 'photo', $this->meta($added));
            $added->delete();
        }
    }

    /** The photo's file: in the storage disk (uploads) or in the public folder (the default pictures that come with the site) */
    private function fileExists(string $path, string $name): bool
    {
        return $name !== '' && (Storage::disk('public')->exists($path . '/' . $name) || is_file(public_path($path . '/' . $name)));
    }

    private function meta(Media $m): array
    {
        return ['media_id' => $m->id, 'role' => $m->media_role, 'name' => $m->name, 'path' => $m->path, 'type' => $m->type, 'size' => $m->size, 'mime' => $m->mime, 'sort_order' => $m->sort_order];
    }
}
