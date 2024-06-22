<?php

namespace App\Http\Controllers\Admin\Place;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\Admin\Place\StateRequest;
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
    public function create() : View
    {
        return view('admin.place.place.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function edit(string $id) : View
    {
        return view('admin.place.place.edit');
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
