<?php

namespace App\Http\Controllers\Admin\Place;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\Admin\Place\PlaceRequest;
use App\Repositories\Place\CityRepository;
use App\Repositories\Place\PlaceRepository;
use App\Models\Place;

class PlaceController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PlaceRepository $placeRepository) : View
    {
        if (!hasPermission('can_view_place')) {
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

        $places = $placeRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_place',
            'create' => 'can_create_place',
            'update' => 'can_update_place',
            'delete' => 'can_delete_place',
        ];

        return view('admin.place.place.index',compact('places','permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CityRepository $cityRepository) : View
    {
        if (!hasPermission('can_create_place')) {
            $this->unauthorized();
        }

        $cities=$cityRepository->pluck('name','id')->toArray();
        return view('admin.place.place.create',compact('cities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlaceRequest $request, PlaceRepository $placeRepository)
    {
        if (!hasPermission('can_create_place')) {
            $this->unauthorized();
        }
        // try {
            $placeRepository->create($request->validated());
            return redirect()->route('admin.places.index')->with([
                'message'    => 'Place added successfully.',
                'alert-type' => 'success'
            ]);
        // } catch (\Exception $e) {
            return redirect()->back()->with('notification', [
                [ 'type' => 'error', 'message' => 'Place could not be created' ]
            ]);
        // }
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
    public function edit(CityRepository $cityRepository,Place $place) : View
    {
        if (!hasPermission('can_update_place')) {
            $this->unauthorized();
        }

        $cities = $cityRepository->pluck('name','id')->toArray();

        return view('admin.place.place.edit',compact('place','cities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PlaceRequest $request,PlaceRepository $placeRepository, $place)
    {
        if (!hasPermission('can_update_place')) {
            $this->unauthorized();
        }
        try {
            $place = $placeRepository->getModel($place);
            $placeRepository->update($request->validated(), $place);

            return redirect()->route('admin.places.index')->with([
                'message'    => 'Place updated successfully.',
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
     * Remove the specified resource from storage.
     */
    public function destroy(PlaceRepository $placeRepository, $place)
    {
        if (!hasPermission('can_delete_place')) {
            $this->unauthorized();
        }

        try {
            $place = $placeRepository->getModel($place);
            $placeRepository->delete($place->id);

            return redirect()->route('admin.places.index')->with([
                'message'    => 'Place deleted successfully.',
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
