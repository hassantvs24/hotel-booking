<?php

namespace App\Http\Controllers\API\Admin\Location;

use App\Http\Controllers\BaseController;
use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Services\Location\LocationImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The world list for the admin: pick a country, see its divisions, then the districts, then the upazilas.
 * Read-only; editing stays on the Countries / States / Cities pages.
 */
class LocationBrowseController extends BaseController
{
    private const LEVELS = ['countries', 'states', 'cities', 'areas'];

    /** GET location/browse?level=states&parent_id=19&search=dha&page=1 */
    public function index(Request $request): JsonResponse
    {
        $level = $request->input('level', 'countries');
        abort_unless(in_array($level, self::LEVELS, true), 422, 'Unknown level.');

        $parentId = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;
        $search   = trim((string) LocationImporter::nfc((string) $request->input('search', '')));
        $perPage  = min(max((int) $request->input('per_page', 25), 5), 100);

        $query = match ($level) {
            'countries' => Country::query()->withCount('states')->orderBy('name'),
            'states'    => State::query()->withCount('cities')->orderBy('name')
                ->when($parentId, fn ($q) => $q->where('country_id', $parentId)),
            'cities'    => City::query()->without('photo')->withCount('areas')->orderBy('name')
                ->when($parentId, fn ($q) => $q->where('state_id', $parentId)),
            'areas'     => Area::query()->orderBy('name')
                ->when($parentId, fn ($q) => $q->where('city_id', $parentId)),
        };

        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('native_name', 'like', $like));
        }

        $page = $query->paginate($perPage);
        $ids  = $page->getCollection()->pluck('id')->all();
        $hotels = $this->hotelCounts($level, $ids);

        $rows = $page->getCollection()->map(function ($r) use ($level, $hotels) {
            return [
                'id'             => $r->id,
                'name'           => $r->name,
                'native_name'    => $r->native_name,
                'type'           => $level === 'countries' ? $r->region : $r->type,
                'code'           => $level === 'countries' ? $r->short_name : null,
                'flag'           => $level === 'countries' ? $r->flag : null,
                'phone_code'     => $level === 'countries' ? $r->phone_code : null,
                'currency_code'  => $level === 'countries' ? $r->currency_code : null,
                'children_count' => match ($level) {
                    'countries' => $r->states_count,
                    'states'    => $r->cities_count,
                    'cities'    => $r->areas_count,
                    default     => null,
                },
                'hotels_count'   => $hotels[$r->id] ?? 0,
            ];
        })->values();

        return $this->sendSuccess([
            'level' => $level,
            'trail' => $this->trail($level, $parentId),
            'rows'  => $rows,
            'pagination' => [
                'total' => $page->total(), 'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(), 'per_page' => $page->perPage(),
            ],
        ]);
    }

    /** GET location/browse/search?q=kaliakair — a name anywhere in the world list, with where it sits */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) LocationImporter::nfc((string) $request->input('q', '')));

        if (mb_strlen($q) < 2) {
            return $this->sendSuccess(['results' => []]);
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
        $match = fn ($query) => $query->where('name', 'like', $like)->orWhere('native_name', 'like', $like);

        $results = collect();

        foreach (Country::where($match)->orderBy('name')->limit(5)->get() as $c) {
            $results->push(['level' => 'countries', 'id' => $c->id, 'name' => $c->name, 'native_name' => $c->native_name, 'path' => [], 'trail' => []]);
        }

        foreach (State::with('country')->where($match)->orderBy('name')->limit(8)->get() as $s) {
            $results->push([
                'level' => 'states', 'id' => $s->id, 'name' => $s->name, 'native_name' => $s->native_name, 'type' => $s->type,
                'path' => [$s->country?->name], 'trail' => $this->trail('states', $s->country_id),
            ]);
        }

        foreach (City::without('photo')->with('state.country')->where($match)->orderBy('name')->limit(8)->get() as $c) {
            $results->push([
                'level' => 'cities', 'id' => $c->id, 'name' => $c->name, 'native_name' => $c->native_name, 'type' => $c->type,
                'path' => [$c->state?->name, $c->state?->country?->name], 'trail' => $this->trail('cities', $c->state_id),
            ]);
        }

        foreach (Area::with('city.state.country')->where($match)->orderBy('name')->limit(10)->get() as $a) {
            $results->push([
                'level' => 'areas', 'id' => $a->id, 'name' => $a->name, 'native_name' => $a->native_name, 'type' => $a->type,
                'path' => [$a->city?->name, $a->city?->state?->name, $a->city?->state?->country?->name],
                'trail' => $this->trail('areas', $a->city_id),
            ]);
        }

        return $this->sendSuccess(['results' => $results->values()]);
    }

    /** The parents above the list being shown, from the country down: [{level, id, name}] */
    private function trail(string $level, ?int $parentId): array
    {
        if (!$parentId || $level === 'countries') {
            return [];
        }

        $trail = [];

        if ($level === 'areas') {
            $city = City::without('photo')->withTrashed()->with('state.country')->find($parentId);
            if ($city) {
                $trail = $this->chain($city->state?->country, $city->state, $city);
            }
        } elseif ($level === 'cities') {
            $state = State::with('country')->find($parentId);
            if ($state) {
                $trail = $this->chain($state->country, $state);
            }
        } else {
            $country = Country::find($parentId);
            if ($country) {
                $trail = $this->chain($country);
            }
        }

        return $trail;
    }

    private function chain($country = null, $state = null, $city = null): array
    {
        return array_values(array_filter([
            $country ? ['level' => 'countries', 'id' => $country->id, 'name' => $country->name] : null,
            $state   ? ['level' => 'states',    'id' => $state->id,   'name' => $state->name]   : null,
            $city    ? ['level' => 'cities',    'id' => $city->id,    'name' => $city->name]    : null,
        ]));
    }

    /**
     * Published hotels under each row of the page.
     *
     * @return array<int,int> row id → number of hotels
     */
    private function hotelCounts(string $level, array $ids): array
    {
        if (!$ids || $level === 'areas') {
            return [];
        }

        $q = DB::table('properties')
            ->join('places', 'places.id', '=', 'properties.place_id')
            ->whereNull('properties.deleted_at')
            ->where('properties.status', 'Published');

        $key = match ($level) {
            'cities'    => 'places.city_id',
            'states'    => 'cities.state_id',
            'countries' => 'states.country_id',
        };

        if ($level !== 'cities') {
            $q->join('cities', 'cities.id', '=', 'places.city_id');
        }
        if ($level === 'countries') {
            $q->join('states', 'states.id', '=', 'cities.state_id');
        }

        return $q->whereIn($key, $ids)
            ->groupBy($key)
            ->selectRaw("$key as k, count(properties.id) as n")
            ->pluck('n', 'k')
            ->map(fn ($n) => (int) $n)
            ->all();
    }
}
