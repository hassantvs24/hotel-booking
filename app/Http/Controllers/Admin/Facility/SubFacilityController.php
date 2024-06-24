<?php

namespace App\Http\Controllers\Admin\Facility;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Facility\SubFacilityRequest;
use App\Models\FacilitySub;
use App\Repositories\Facility\FacilityRepository;
use App\Repositories\Facility\SubFacilityRepository;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubFacilityController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, SubFacilityRepository $subFacilityRepository) : View
    {
        if (!hasPermission('can_view_sub_facility')) {
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

        $subFacilities = $subFacilityRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_sub_facility',
            'create' => 'can_create_sub_facility',
            'update' => 'can_update_sub_facility',
            'delete' => 'can_delete_sub_facility',
        ];

        return view('admin.facility.sub-facility.index', compact('subFacilities', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SubFacilityRequest $request, SubFacilityRepository $subFacilityRepository) : RedirectResponse
    {
        if (!hasPermission('can_create_facility')) {
            $this->unauthorized();
        }

        try {
            $subFacilityRepository->create($request->validated());

            return redirect()->route('admin.facilities.sub-facilities.index')->with([
                'message'    => 'Sub facility created successfully.',
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
    public function create(FacilityRepository $facilityRepository) : View
    {
        if (!hasPermission('can_create_sub_facility')) {
            $this->unauthorized();
        }

        $facilities = $facilityRepository->pluck('name', 'id')->toArray();

        return view('admin.facility.sub-facility.create', compact('facilities'));
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
    public function edit(FacilityRepository $facilityRepository, FacilitySub $subFacility) : View
    {
        if (!hasPermission('can_update_sub_facility')) {
            $this->unauthorized();
        }

        $facilities = $facilityRepository->pluck('name', 'id')->toArray();

        return view('admin.facility.sub-facility.edit', compact('subFacility', 'facilities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        SubFacilityRequest $request,
        SubFacilityRepository $subFacilityRepository,
        $subFacility
    ) : RedirectResponse {
        if (!hasPermission('can_update_sub_facility')) {
            $this->unauthorized();
        }

        try {
            $subFacility = $subFacilityRepository->getModel($subFacility);
            $subFacilityRepository->update($request->validated(), $subFacility);

            return redirect()->route('admin.facilities.sub-facilities.index')->with([
                'message'    => 'Sub Facility updated successfully.',
                'alert-type' => 'success'
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something Went Wrong');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubFacilityRepository $subFacilityRepository, $subFacility) : RedirectResponse
    {
        if (!hasPermission('can_delete_place')) {
            $this->unauthorized();
        }

        try {

            $subFacility = $subFacilityRepository->getModel($subFacility);
            $subFacilityRepository->delete($subFacility->id);

            return redirect()->route('admin.facilities.sub-facilities.index')->with([
                'message'    => 'Sub Facility deleted successfully.',
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

