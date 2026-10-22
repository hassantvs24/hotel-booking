<?php

namespace App\Http\Controllers\API\Admin\Location;

use App\Http\Controllers\BaseController;
use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\State;
use App\Services\Location\LocationImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Add, rename and remove a row of the world list, at any level: country, division/state, district/city, upazila/area.
 * Only the platform admin gets here (the `admin.write` group); reading is in LocationBrowseController.
 */
class LocationManageController extends BaseController
{
    /** level → [model, parent column, parent table, fields that can be typed] */
    private const LEVELS = [
        'countries' => [Country::class, null, null, ['native_name', 'short_name', 'country_code', 'phone_code', 'currency', 'currency_code', 'region']],
        'states'    => [State::class, 'country_id', 'countries', ['native_name', 'type']],
        'cities'    => [City::class, 'state_id', 'states', ['native_name', 'type', 'lat', 'long']],
        'areas'     => [Area::class, 'city_id', 'cities', ['native_name', 'type']],
    ];

    public function store(Request $request, string $level): JsonResponse
    {
        [$model, $parentCol, $parentTable, $fields] = $this->level($level);

        $request->merge($this->clean($request, $fields));

        // the list being shown is the parent: "parent_id" is the country for a division, the division for a district ...
        $parentId = $parentCol ? $request->input('parent_id') : null;
        if ($parentCol) {
            $request->merge([$parentCol => $parentId]);
        }

        $validated = $request->validate($this->rules($level, null, $parentCol, $parentTable, $parentId));

        $row = $model::create($this->payload($level, $validated, $fields) + ($parentCol ? [$parentCol => (int) $validated[$parentCol]] : []));

        return $this->sendSuccess(['id' => $row->id], 'Added.', 201);
    }

    public function update(Request $request, string $level, int $id): JsonResponse
    {
        [$model, $parentCol, $parentTable, $fields] = $this->level($level);
        $row = $model::findOrFail($id);

        $request->merge($this->clean($request, $fields));
        $validated = $request->validate($this->rules($level, $row->id, $parentCol, $parentTable, $parentCol ? $row->{$parentCol} : null));

        // where it sits is not changed here; only the details of the row itself
        $row->update($this->payload($level, $validated, $fields));

        return $this->sendSuccess(['id' => $row->id], 'Saved.');
    }

    public function destroy(string $level, int $id): JsonResponse
    {
        [$model] = $this->level($level);
        $row = $model::findOrFail($id);

        if ($why = $this->blocker($level, $row)) {
            throw ValidationException::withMessages(['name' => [$why]]);
        }

        $row->delete();

        return $this->sendSuccess(null, 'Removed.');
    }

    // ── Rules ───────────────────────────────────────────────

    private function level(string $level): array
    {
        abort_unless(isset(self::LEVELS[$level]), 404);

        return self::LEVELS[$level];
    }

    private function rules(string $level, ?int $id, ?string $parentCol, ?string $parentTable, $parentId): array
    {
        [$model] = self::LEVELS[$level];

        $rules = [
            'name' => ['required', 'string', 'max:255', function ($attr, $value, $fail) use ($model, $id, $parentCol, $parentId) {
                $same = $model::query()->where('name', trim($value))
                    ->when($id, fn ($q) => $q->where('id', '!=', $id))
                    ->when($parentCol, fn ($q) => $q->where($parentCol, $parentId));

                if ($same->exists()) {
                    $fail('There is already one called "' . trim($value) . '" here.');
                }
            }],
            'native_name' => ['nullable', 'string', 'max:255'],
            'type'        => ['nullable', 'string', 'max:60'],
        ];

        if ($parentCol && !$id) {
            $rules[$parentCol] = ['required', 'integer', Rule::exists($parentTable, 'id')->whereNull('deleted_at')];
        }

        if ($level === 'countries') {
            unset($rules['type']);
            $rules += [
                'short_name'    => ['nullable', 'alpha', 'size:2', Rule::unique('countries', 'short_name')->ignore($id)->whereNull('deleted_at')],
                'country_code'  => ['nullable', 'alpha', 'size:3'],
                'phone_code'    => ['nullable', 'regex:/^\+?[0-9]{1,6}$/'],
                'currency'      => ['nullable', 'string', 'max:100'],
                'currency_code' => ['nullable', 'alpha', 'size:3'],
                'region'        => ['nullable', 'string', 'max:60'],
            ];
        }

        if ($level === 'cities') {
            $rules += ['lat' => ['nullable', 'numeric', 'between:-90,90'], 'long' => ['nullable', 'numeric', 'between:-180,180']];
        }

        return $rules;
    }

    /** Spaces trimmed and squeezed, Bangla made uniform, ISO codes in capitals; an empty box is "nothing" */
    private function clean(Request $request, array $fields): array
    {
        $out = [];

        foreach (array_merge(['name'], $fields) as $f) {
            if (!$request->has($f)) {
                continue;
            }

            $v = $request->input($f);
            $v = is_string($v) ? trim(preg_replace('/\s+/u', ' ', $v)) : $v;
            $v = LocationImporter::nfc($v);
            $out[$f] = ($v === '' || $v === 'null') ? null : $v;
        }

        foreach (['short_name', 'country_code', 'currency_code'] as $code) {
            if (isset($out[$code])) {
                $out[$code] = strtoupper($out[$code]);
            }
        }

        if (isset($out['phone_code']) && !str_starts_with($out['phone_code'], '+') && $out['phone_code'] !== '') {
            $out['phone_code'] = '+' . $out['phone_code'];
        }

        return $out;
    }

    private function payload(string $level, array $validated, array $fields): array
    {
        $payload = ['name' => $validated['name']];

        foreach ($fields as $f) {
            if (array_key_exists($f, $validated)) {
                $payload[$f] = $validated[$f];
            }
        }

        return $payload;
    }

    /** Something a row still holds up: you cannot remove a country that has divisions in it */
    private function blocker(string $level, $row): ?string
    {
        switch ($level) {
            case 'countries':
                $n = State::where('country_id', $row->id)->count();

                return $n ? "{$row->name} still has $n division" . ($n === 1 ? '' : 's') . ' or states in it. Remove those first.' : null;

            case 'states':
                $n = City::where('state_id', $row->id)->count();

                return $n ? "{$row->name} still has $n district" . ($n === 1 ? '' : 's') . ' or cit' . ($n === 1 ? 'y' : 'ies') . ' in it. Remove those first.' : null;

            case 'cities':
                $areas = Area::where('city_id', $row->id)->count();
                $places = Place::where('city_id', $row->id)->count();

                if ($areas) {
                    return "{$row->name} still has $areas upazila" . ($areas === 1 ? '' : 's') . ' or area' . ($areas === 1 ? '' : 's') . ' in it. Remove those first.';
                }

                return $places ? "{$row->name} has $places place" . ($places === 1 ? '' : 's') . ' set in it (the spots hotels are placed at). Move or remove those first.' : null;
        }

        return null;
    }
}
