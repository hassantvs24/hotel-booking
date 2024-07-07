<?php

namespace App\Http\Controllers\Admin\Place;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Place\CityRequest;
use App\Models\City;
use App\Repositories\Place\CityRepository;
use App\Repositories\Place\StateRepository;
use App\Traits\MediaMan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CityController extends BaseController
{
    use MediaMan;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, CityRepository $cityRepository) : View
    {
        if (!hasPermission('can_view_city')) {
            $this->unauthorized();
        }

        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => [],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );

        $cities = $cityRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_city',
            'create' => 'can_create_city',
            'update' => 'can_update_city',
            'delete' => 'can_delete_city',
        ];

        return view('admin.place.city.index', compact('cities', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(StateRepository $stateRepository) : View
    {
        if (!hasPermission('can_create_city')) {
            $this->unauthorized();
        }

        $states = $stateRepository->pluck('name', 'id')->toArray();

        return view('admin.place.city.create', compact('states'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CityRequest $request, CityRepository $cityRepository) : RedirectResponse
    {
        if (!hasPermission('can_create_city')) {
            $this->unauthorized();
        }

        try {
            $city = $cityRepository->create(
                $request->except(['photo'])
            );

            if ($request->hasFile('photo')) {
                $photo = $this->storeFile($request->file('photo'), 'cities');
                $city->photo()->create([
                    ...$photo,
                    'media_role' => 'place_image'
                ]);
            }

            return redirect()->route('admin.places.cities.index')->with([
                'message'    => 'City added successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something went wrong.',
                'alert-type' => 'error'
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StateRepository $stateRepository, City $city) : View
    {
        if (!hasPermission('can_update_city')) {
            $this->unauthorized();
        }

        $states = $stateRepository->pluck('name', 'id')->toArray();

        return view('admin.place.city.edit', compact('city', 'states'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CityRequest $request, CityRepository $cityRepository, $city) : RedirectResponse
    {
        if (!hasPermission('can_update_city')) {
            $this->unauthorized();
        }

//        try {
        $model = $cityRepository->getModel($city);
        $city = $cityRepository->update($request->except(['photo']), $model);

        if ($request->hasFile('photo')) {

            if ($model->photo()->exists()) {
                $this->deleteFile($model->photo->name, 'cities');
                $model->photo()->delete();
            }

            $photo = $this->storeFile($request->file('photo'), 'cities');
            $model->photo()->create([
                ...$photo,
                'media_role' => 'place_image'
            ]);
        }

        return redirect()->route('admin.places.cities.index')->with([
            'message'    => 'City updated successfully.',
            'alert-type' => 'success'
        ]);
//        } catch (\Exception $e) {
//            return redirect()->back()->with([
//                'message'    => 'Something went wrong.',
//                'alert-type' => 'error'
//            ]);
//        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CityRepository $cityRepository, $city) : RedirectResponse
    {
        if (!hasPermission('can_delete_city')) {
            $this->unauthorized();
        }

        try {
            $city = $cityRepository->getModel($city);
            $cityRepository->delete($city->id);

            return redirect()->route('admin.places.cities.index')->with([
                'message'    => 'City deleted successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something went wrong.',
                'alert-type' => 'error'
            ]);
        }
    }
}
