<?php

namespace App\Http\Controllers\Admin\Surrounding;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Repositories\Surrounding\SurroundingPlaceRepository;
use App\Http\Requests\Admin\Surrounding\SurroundingPlaceRequest;
use App\Models\SurroundingPlace;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurroundingPlaceController extends Controller
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

        $surroundingplaces  = $surroundingPlaceRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_surrounding_place',
            'create' => 'can_create_surrounding_place',
            'update' => 'can_update_surrounding_place',
            'delete' => 'can_delete_surrounding_place',
        ];

        return view('admin.surrounding.place.index', compact('surroundingplaces', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        return view('admin.surrounding.place.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) : RedirectResponse
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
        return view('admin.surrounding.place.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) : RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) : RedirectResponse
    {
        //
    }
}
