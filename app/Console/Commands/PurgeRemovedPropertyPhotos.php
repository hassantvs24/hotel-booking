<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\PropertyChangeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * A photo an owner removes is not deleted from the disk at once: the admin can bring it back from the change log.
 * After 30 days it is deleted for good (unless it has been put back).
 */
class PurgeRemovedPropertyPhotos extends Command
{
    protected $signature = 'property:purge-removed-photos {--days=30 : how long a removed photo is kept}';

    protected $description = 'Delete the files of property photos that were removed more than 30 days ago';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $n = 0;

        PropertyChangeLog::where('kind', 'photo')->whereNull('new_value')->whereNotNull('old_value')
            ->where('created_at', '<', now()->subDays($days))->orderBy('id')
            ->each(function (PropertyChangeLog $e) use (&$n) {
                $meta = $e->meta ?? [];

                if (!empty($meta['purged']) || empty($meta['name'])) {
                    return;
                }

                // a photo that was put back is in use again: its file stays
                if (!Media::where('name', $meta['name'])->exists()) {
                    Storage::disk('public')->delete(($meta['path'] ?? 'properties') . '/' . $meta['name']);
                    $n++;
                }

                $e->update(['meta' => array_merge($meta, ['purged' => true])]);
            });

        $this->info("Deleted $n removed photo file(s).");

        return self::SUCCESS;
    }
}
