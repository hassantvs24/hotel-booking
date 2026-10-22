<?php

namespace App\Services\Location;

use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fills Country → State/Division → City/District → Area/Upazila from the files in database/data/geo.
 *
 * Safe to run again and again: a row that already exists (matched by its source id, or by its name once
 * words like "Division" are ignored) is completed, never replaced or duplicated, and a row an admin deleted
 * stays deleted. Nothing is ever removed.
 *
 * Data: countries, divisions and districts of the world from dr5hn/countries-states-cities-database (ODbL),
 * the upazilas of Bangladesh and the Bangla names from nuhil/bangladesh-geocode (MIT).
 */
class LocationImporter
{
    private const SOURCE = 'dr5hn';

    /** Spellings the two lists disagree on */
    private const ALIASES = [
        'chattogram' => 'chittagong', 'chattagram' => 'chittagong', 'barisal' => 'barishal',
        'comilla' => 'cumilla', 'bogra' => 'bogura', 'jessore' => 'jashore', 'dacca' => 'dhaka',
        'netrokona' => 'netrakona',
    ];

    private string $dir;

    /** @var array<string,int> what each step did, for the report */
    public array $stats = [];

    /** @var array<int,string> existing rows the lists do not know, for the report */
    public array $unmatched = [];

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? database_path('data/geo');
    }

    public function run(bool $dryRun = false): void
    {
        $this->stats = [
            'countries_created' => 0, 'countries_filled' => 0,
            'states_created' => 0, 'states_matched' => 0,
            'cities_created' => 0, 'cities_matched' => 0,
            'areas_created' => 0, 'areas_existing' => 0,
            'skipped_deleted' => 0,
        ];
        $this->unmatched = [];

        DB::beginTransaction();

        try {
            $countryIds = $this->countries();
            $stateIds   = $this->states($countryIds);
            $cityIds    = $this->cities($stateIds);
            $this->areas($cityIds);
            $this->reportStrays();

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ── Countries ───────────────────────────────────────────

    /** @return array<string,int> ISO2 code → our country id */
    private function countries(): array
    {
        $existing = Country::withTrashed()->get();
        $ids      = [];

        foreach ($this->load('countries.json') as $c) {
            $row = $existing->first(fn ($e) => strtoupper((string) $e->short_name) === $c['iso2'])
                ?? $existing->first(fn ($e) => $this->normalize($e->name) === $this->normalize($c['name']));

            $fill = [
                'short_name'    => $c['iso2'],
                'country_code'  => $c['iso3'],
                'currency'      => $c['currency'],
                'currency_code' => $c['currency_code'],
                'phone_code'    => $c['phone'],
                'flag'          => $c['emoji'],
                'native_name'   => $this->nfc($c['native']),
                'region'        => $c['region'],
            ];

            if (!$row) {
                $row = Country::create(['name' => $c['name']] + $fill);
                $existing->push($row);
                $this->stats['countries_created']++;
            } elseif ($row->trashed()) {
                $this->stats['skipped_deleted']++;
            } else {
                // only the blanks: a value an admin typed is never overwritten
                $blank = array_filter($fill, fn ($v, $k) => blank($row->{$k}) && filled($v), ARRAY_FILTER_USE_BOTH);
                if ($blank) {
                    $row->update($blank);
                    $this->stats['countries_filled']++;
                }
            }

            $ids[$c['iso2']] = $row->id;
        }

        return $ids;
    }

    // ── States / divisions ──────────────────────────────────

    /** @return array<int,int> source state id → our state id */
    private function states(array $countryIds): array
    {
        $map = [];
        $byCountry = State::withTrashed()->get()->groupBy('country_id');

        foreach ($this->load('states.json') as $s) {
            if ($s['tier'] !== 1 || !isset($countryIds[$s['cc']])) {
                continue;
            }

            $countryId = $countryIds[$s['cc']];
            $rows      = $byCountry->get($countryId, collect());
            $ext       = self::SOURCE . ':' . $s['id'];

            $row = $rows->first(fn ($e) => $e->external_id === $ext)
                ?? $rows->first(fn ($e) => blank($e->external_id) && $this->normalize($e->name) === $this->normalize($s['name']));

            if (!$row) {
                $row = State::create([
                    'name' => $s['name'], 'native_name' => $this->nfc($s['native']), 'type' => $s['type'],
                    'country_id' => $countryId, 'external_id' => $ext,
                ]);
                $byCountry[$countryId] = $rows->push($row);
                $this->stats['states_created']++;
            } elseif ($row->trashed()) {
                $this->stats['skipped_deleted']++;
            } else {
                $this->completeRow($row, ['type' => $s['type'], 'native_name' => $this->nfc($s['native']), 'external_id' => $ext]);
                $this->stats['states_matched']++;
            }

            $map[$s['id']] = $row->id;
        }

        return $map;
    }

    // ── Cities / districts ──────────────────────────────────

    /** @return array<int,int> source district id → our city id */
    private function cities(array $stateIds): array
    {
        $map = [];
        $byState = City::withTrashed()->get()->groupBy('state_id');

        foreach ($this->load('states.json') as $d) {
            if ($d['tier'] !== 2 || !isset($stateIds[$d['parent']])) {
                continue;
            }

            $stateId = $stateIds[$d['parent']];
            $rows    = $byState->get($stateId, collect());
            $ext     = self::SOURCE . ':' . $d['id'];

            $row = $rows->first(fn ($e) => $e->external_id === $ext)
                ?? $rows->first(fn ($e) => blank($e->external_id) && $this->normalize($e->name) === $this->normalize($d['name']));

            if (!$row) {
                $row = City::create([
                    'name' => $d['name'], 'native_name' => $this->nfc($d['native']), 'type' => $d['type'],
                    'lat' => $d['lat'], 'long' => $d['lng'], 'state_id' => $stateId, 'external_id' => $ext,
                ]);
                $byState[$stateId] = $rows->push($row);
                $this->stats['cities_created']++;
            } elseif ($row->trashed()) {
                $this->stats['skipped_deleted']++;
            } else {
                $this->completeRow($row, [
                    'type' => $d['type'], 'native_name' => $this->nfc($d['native']), 'external_id' => $ext,
                    'lat' => $d['lat'], 'long' => $d['lng'],
                ]);
                $this->stats['cities_matched']++;
            }

            $map[$d['id']] = $row->id;
        }

        return $map;
    }

    // ── Areas / upazilas ────────────────────────────────────

    private function areas(array $cityIds): void
    {
        $byCity = Area::withTrashed()->get()->groupBy('city_id');

        foreach ($this->load('areas.json') as $a) {
            $cityId = $cityIds[$a['district_id']] ?? null;
            if (!$cityId) {
                continue;
            }

            $rows = $byCity->get($cityId, collect());
            $row  = $rows->first(fn ($e) => $this->normalize($e->name) === $this->normalize($a['name']));

            if ($row) {
                $row->trashed() ? $this->stats['skipped_deleted']++ : $this->stats['areas_existing']++;
                continue;
            }

            $row = Area::create([
                'city_id' => $cityId, 'name' => $a['name'], 'native_name' => $this->nfc($a['native']), 'type' => $a['type'],
            ]);
            $byCity[$cityId] = $rows->push($row);
            $this->stats['areas_created']++;
        }
    }

    // ── Helpers ─────────────────────────────────────────────

    /** Rows made by hand or by a map search that the lists do not know: reported, never touched */
    private function reportStrays(): void
    {
        foreach (State::whereNull('external_id')->with('country')->get() as $s) {
            $this->unmatched[] = "state #{$s->id} {$s->name} ({$s->country?->name})";
        }
        foreach (City::whereNull('external_id')->with('state')->get() as $c) {
            $this->unmatched[] = "city #{$c->id} {$c->name} (under {$c->state?->name})";
        }
    }

    /** Fill only what is empty on the row */
    private function completeRow($row, array $values): void
    {
        $blank = array_filter($values, fn ($v, $k) => blank($row->{$k}) && filled($v), ARRAY_FILTER_USE_BOTH);

        if ($blank) {
            $row->update($blank);
        }
    }

    /** Bangla typed on a keyboard is in composed form (য় as য + a dot); the lists have it either way, so make it one way */
    public static function nfc(?string $text): ?string
    {
        return ($text !== null && class_exists(\Normalizer::class)) ? (\Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text) : $text;
    }

    private function load(string $file): array
    {
        return json_decode(file_get_contents($this->dir . '/' . $file), true, 512, JSON_THROW_ON_ERROR);
    }

    public function normalize(string $name): string
    {
        $n = Str::lower(Str::ascii(trim($name)));
        $n = preg_replace('/\b(division|district|zila|city|metropolitan|province|state|region)\b/u', '', $n);
        $n = preg_replace('/[^a-z0-9]/', '', $n);

        return self::ALIASES[$n] ?? ($n !== '' ? $n : Str::lower(Str::ascii($name)));
    }
}
