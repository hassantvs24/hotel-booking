<?php

namespace App\Services\Property;

use App\Models\PropertyRequest;
use App\Services\Verification\VerificationService;
use Illuminate\Support\Facades\Storage;

/**
 * Throws an unfinished property draft away completely: the draft itself, the photos it uploaded and the trade licence
 * it holds. (The owner's NID belongs to the person, not to the draft, so it stays.)
 * Only drafts. A submitted, approved or sent-back application is never touched here.
 */
class DraftCleaner
{
    /** A draft nobody typed anything into is thrown away after this many days without a change */
    public const EMPTY_AFTER_DAYS = 3;

    public function purge(PropertyRequest $draft): void
    {
        abort_unless($draft->is_draft, 403, 'Only an unfinished draft can be deleted.');

        foreach (($draft->draft_data['step_5']['photos'] ?? []) as $photo) {
            if (!empty($photo['name'])) {
                Storage::disk($photo['disk'] ?? 'public')->delete(trim($photo['path'] ?? 'properties', '/') . '/' . $photo['name']);
            }
        }

        if ($draft->license) {
            app(VerificationService::class)->deleteFiles([$draft->license->file_path]);
            $draft->license->forceDelete();
        }

        $draft->forceDelete();
    }

    /** True when the owner never put anything of their own in: no name, no address, no description, no photo, no licence */
    public function isEmpty(PropertyRequest $draft): bool
    {
        $data = $draft->draft_data ?? [];

        return trim((string) $draft->property_title) === ''
            && trim((string) $draft->address) === ''
            && trim((string) $draft->description) === ''
            && trim((string) ($data['step_2']['name'] ?? '')) === ''
            && trim((string) ($data['step_3']['address'] ?? '')) === ''
            && empty($data['step_5']['photos'] ?? [])
            && !$draft->license()->exists();
    }

    /** Throw away the empty drafts that have been left alone for EMPTY_AFTER_DAYS days. Returns how many. */
    public function purgeEmptyDrafts(): int
    {
        $removed = 0;

        PropertyRequest::query()
            ->where('is_draft', true)
            ->where('updated_at', '<', now()->subDays(self::EMPTY_AFTER_DAYS))
            ->each(function (PropertyRequest $draft) use (&$removed) {
                if ($this->isEmpty($draft)) {
                    $this->purge($draft);
                    $removed++;
                }
            });

        return $removed;
    }
}
