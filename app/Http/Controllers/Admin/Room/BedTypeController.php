<?php

namespace App\Http\Controllers\Admin\Room;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Room\BedTypeRequest;
use App\Models\BedType;
use App\Repositories\Room\BedTypeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BedTypeController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, BedTypeRepository $bedTypeRepository): View
    {
        if (!hasPermission('can_view_bed_type')) {
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

        $bedTypes = $bedTypeRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_bed_type',
            'create' => 'can_create_bed_type',
            'update' => 'can_update_bed_type',
            'delete' => 'can_delete_bed_type',
        ];

        return view('admin.room.bed-type.index', compact('bedTypes', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        if (!hasPermission('can_create_bed_type')) {
            $this->unauthorized();
        }

        return view('admin.room.bed-type.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BedTypeRequest $request, BedTypeRepository $bedTypeRepository): RedirectResponse
    {
        if (!hasPermission('can_create_bed_type')) {
            $this->unauthorized();
        }

        try {
            $bedTypeRepository->create($request->validated());

            return redirect()->route('admin.rooms.bed-types.index')->with([
                'message'    => 'Bed Type created successfully.',
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
    public function edit(BedType $bedType): View
    {
        if (!hasPermission('can_update_bed_type')) {
            $this->unauthorized();
        }

        return view('admin.room.bed-type.edit', compact('bedType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BedTypeRequest $request, BedTypeRepository $bedTypeRepository, $bedType): RedirectResponse
    {
        if (!hasPermission('can_update_bed_type')) {
            $this->unauthorized();
        }
        try {
            $bedType = $bedTypeRepository->getModel($bedType);
            $bedTypeRepository->update($request->validated(), $bedType);

            return redirect()->route('admin.rooms.bed-types.index')->with([
                'message'    => 'Bed Type updated successfully.',
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
    public function destroy(BedTypeRepository $bedTypeRepository, $bedType): RedirectResponse
    {
        if (!hasPermission('can_delete_bed_type')) {
            $this->unauthorized();
        }
        try {
            $bedType = $bedTypeRepository->getModel($bedType);
            $bedTypeRepository->delete($bedType->id);

            return redirect()->route('admin.rooms.bed-types.index')->with([
                'message'    => 'Bed Type deleted successfully.',
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
