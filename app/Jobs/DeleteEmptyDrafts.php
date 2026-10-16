<?php

// Runs once a day via the scheduler.
// Opening the property wizard creates a draft at once, so people who only looked around leave empty drafts behind.
// An empty draft (nothing typed, no photo, no licence) untouched for 3 days is thrown away. A draft with anything
// of the owner's in it is never removed here: only the owner can delete that, from My Properties.

namespace App\Jobs;

use App\Services\Property\DraftCleaner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class DeleteEmptyDrafts implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(DraftCleaner $cleaner): void
    {
        $removed = $cleaner->purgeEmptyDrafts();

        if ($removed > 0) {
            Log::info("[DeleteEmptyDrafts] Removed {$removed} empty property draft(s).");
        }
    }
}
