<?php

namespace App\Console\Commands;

use App\Services\Location\LocationImporter;
use Illuminate\Console\Command;

class ImportLocations extends Command
{
    protected $signature = 'location:import {--dry-run : show what would change and save nothing}';

    protected $description = 'Fill countries, divisions, districts and upazilas from the world list (safe to run again)';

    public function handle(LocationImporter $importer): int
    {
        $dry = (bool) $this->option('dry-run');

        $importer->run($dry);

        foreach ($importer->stats as $what => $n) {
            $this->line(str_pad($what, 20) . $n);
        }

        if ($importer->unmatched) {
            $this->newLine();
            $this->warn('Rows that are not in the world list (left exactly as they are):');
            foreach ($importer->unmatched as $line) {
                $this->line('  ' . $line);
            }
        }

        $this->info($dry ? 'Dry run: nothing was saved.' : 'Done.');

        return self::SUCCESS;
    }
}
