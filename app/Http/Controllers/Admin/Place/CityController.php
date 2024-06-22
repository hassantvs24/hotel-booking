<?php

namespace App\Http\Controllers\Admin\Place;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Place\CityRequest;
use App\Models\State;
use App\Repositories\Place\CityRepository;
use App\Repositories\Place\StateRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CityController extends BaseController
{
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
    public function store(CityRequest $request, CityRepository $cityRepository) :RedirectResponse
    {
        if (!hasPermission('can_create_city')) {
            $this->unauthorized();
        }

        try {
            $cityRepository->create($request->validated());

            return redirect()->route('admin.places.cities.index')->with([
                'message'    => 'City added successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('notification', [
                [ 'type' => 'error', 'message' => 'City could not be created' ]
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
