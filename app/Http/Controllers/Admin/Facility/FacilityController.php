<?php

namespace App\Http\Controllers\Admin\Facility;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Facility\FacilityRequest;
use App\Models\Facility;
use App\Repositories\Facility\FacilityRepository;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacilityController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, FacilityRepository $facilityRepository) : View
    {
        if (!hasPermission('can_view_facility')) {
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

        $facilities = $facilityRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_facility',
            'create' => 'can_create_facility',
            'update' => 'can_update_facility',
            'delete' => 'can_delete_facility',
        ];

        return view('admin.facility.facility.index', compact('facilities', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FacilityRequest $request, FacilityRepository $facilityRepository) : RedirectResponse
    {
        if (!hasPermission('can_create_facility')) {
            $this->unauthorized();
        }
        try {
            $facilityRepository->create($request->validated());

            return redirect()->route('admin.facilities.index')->with([
                'message'    => 'Facility created successfully.',
                'alert-type' => 'success'
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something Went Wrong',
                'alert-type' => 'error'
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        if (!hasPermission('can_create_facility')) {
            $this->unauthorized();
        }
        return view('admin.facility.facility.create');
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
    public function edit(FacilityRepository $facilityRepository, Facility $facility) : View
    {
        if (!hasPermission('can_update_facility')) {
            $this->unauthorized();
        }

        return view('admin.facility.facility.edit', compact('facility'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        FacilityRequest $request,
        FacilityRepository $facilityRepository,
        $facility
    ) : RedirectResponse {
        if (!hasPermission('can_update_facility')) {
            $this->unauthorized();
        }

        try {
            $facility = $facilityRepository->getModel($facility);
            $facilityRepository->update($request->validated(), $facility);

            return redirect()->route('admin.facilities.index')->with([
                'message'    => 'Facility updated successfully.',
                'alert-type' => 'success'
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something Went Wrong');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FacilityRepository $facilityRepository, $facility) : RedirectResponse
    {
        if (!hasPermission('can_delete_place')) {
            $this->unauthorized();
        }

        try {

            $facility = $facilityRepository->getModel($facility);
            $facilityRepository->delete($facility->id);

            return redirect()->route('admin.facilities.index')->with([
                'message'    => 'Facility deleted successfully.',
                'alert-type' => 'success'
            ]);

        } catch (Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something went wrong.',
                'alert-type' => 'error'
            ]);
        }
    }
}
