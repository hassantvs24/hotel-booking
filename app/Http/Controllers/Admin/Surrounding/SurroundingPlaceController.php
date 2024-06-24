<?php

namespace App\Http\Controllers\Admin\Surrounding;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Surrounding\SurroundingPlaceRequest;
use App\Models\SurroundingPlace;
use App\Repositories\Place\PlaceRepository;
use App\Repositories\Surrounding\SurroundingPlaceRepository;
use App\Repositories\Surrounding\SurroundingRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurroundingPlaceController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, SurroundingPlaceRepository $surroundingPlaceRepository) : View
    {
        if (!hasPermission('can_view_surrounding_place')) {
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

        $surroundingPlaces = $surroundingPlaceRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_surrounding_place',
            'create' => 'can_create_surrounding_place',
            'update' => 'can_update_surrounding_place',
            'delete' => 'can_delete_surrounding_place',
        ];

        return view('admin.surrounding.place.index', compact('surroundingPlaces', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(PlaceRepository $placeRepository, SurroundingRepository $surroundingRepository) : View
    {
        if (!hasPermission('can_create_surrounding_place')) {
            $this->unauthorized();
        }
        $places = $placeRepository->pluck('name', 'id')->toArray();
        $surroundings = $surroundingRepository->pluck('name', 'id')->toArray();

        return view('admin.surrounding.place.create', compact('places', 'surroundings'));
    }

    /**
     * Store a newly created resource in storage.
     * @throws \Exception
     */
    public function store(
        SurroundingPlaceRequest $request,
        SurroundingPlaceRepository $surroundingPlaceRepository
    ) : RedirectResponse {

        if (!hasPermission('can_create_surrounding_place')) {
            $this->unauthorized();
        }

        try {
            $surroundingPlaceRepository->create($request->validated());

            return redirect()->route('admin.surroundings.surrounding-places.index')->with([
                'message'    => 'Surrounding place created successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => $e->getMessage(),
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
    public function edit(
        PlaceRepository $placeRepository,
        SurroundingRepository $surroundingRepository,
        SurroundingPlace $surroundingPlace
    ) : View {
        if (!hasPermission('can_update_surrounding_place')) {
            $this->unauthorized();
        }
        $places = $placeRepository->pluck('name', 'id')->toArray();
        $surroundings = $surroundingRepository->pluck('name', 'id')->toArray();

        return view('admin.surrounding.place.edit', compact('places', 'surroundings', 'surroundingPlace'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        SurroundingPlaceRequest $request,
        SurroundingPlaceRepository $surroundingPlaceRepository,
        $surroundingPlace
    ) : RedirectResponse {
        if (!hasPermission('can_update_surrounding_place')) {
            $this->unauthorized();
        }
        try {
            $surroundingPlace = $surroundingPlaceRepository->getModel($surroundingPlace);
            $surroundingPlaceRepository->update($request->validated(), $surroundingPlace);

            return redirect()->route('admin.surroundings.surrounding-places.index')->with([
                'message'    => 'Surrounding place updated successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something Went Wrong',
                'alert-type' => 'error'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        SurroundingPlaceRepository $surroundingPlaceRepository,
        $surroundingPlace
    ) : RedirectResponse {
        if (!hasPermission('can_delete_surrounding_place')) {
            $this->unauthorized();
        }
        try {
            $surroundingPlace = $surroundingPlaceRepository->getModel($surroundingPlace);
            $surroundingPlaceRepository->delete($surroundingPlace->id);

            return redirect()->route('admin.surroundings.surrounding-places.index')->with([
                'message'    => 'Surrounding place deleted successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something Went Wrong',
                'alert-type' => 'error'
            ]);
        }
    }
}
