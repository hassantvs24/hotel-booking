<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settingEntries = config('site.settings.entries');
        foreach ($settingEntries as $settingEntry) {
            Setting::query()->create([
                'key' => $settingEntry['key'],
                'value' => $settingEntry['value'],
                'value_type' => $settingEntry['value_type'],
                'group' => $settingEntry['group'],
                'admin_only' => $settingEntry['admin_only'],
                'is_deletable' => $settingEntry['is_deletable'],
            ]);
        }
    }
}
