<?php

namespace App\Http\Controllers\API\Admin\Facility;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Facility\SubFacilityRequest;
use App\Models\FacilitySub;
use App\Repositories\Facility\FacilityRepository;
use App\Repositories\Facility\SubFacilityRepository;
use App\Traits\MediaMan;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubFacilityController extends BaseController
{
    use MediaMan;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, SubFacilityRepository $subFacilityRepository): JsonResponse
    {
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
        

        return $this->sendSuccess(['subFacilities' => $subFacilities]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SubFacilityRequest $request, SubFacilityRepository $subFacilityRepository): JsonResponse
    {
        try {
            $subFacility = $subFacilityRepository->create($request->only('name', 'facility_id'));

            if ($request->hasFile('icon')) {
                $icon = $this->storeFile($request->file('icon'), 'facility_icons');
                $subFacility->icon()->create([
                    ...$icon,
                    'media_role' => 'facility_icon'
                ]);
            }

            return $this->sendSuccess($subFacility->load('facility_id', 'icon'), 'Sub Facility Created Successfully');
        } 
        catch (Exception $e) {
            return $this->sendError('Error creating Sub Facility.', [$e->getMessage()]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SubFacilityRequest $request, SubFacilityRepository $subFacilityRepository, string $subFacility): JsonResponse
    {
        try {
            $subFacilityModel = $subFacilityRepository->getModel($subFacility);

            $subFacilityRepository->update($request->only('name', 'facility_id'), $subFacilityModel);

            if (!$request->hasFile('icon') && $request->input('remove_icon')) {
                $this->deleteImage($subFacilityModel);
            }

            if ($request->hasFile('icon')) {
                $this->deleteImage($subFacilityModel);

                $icon = $this->storeFile($request->file('icon'), 'facility_icons');
                $subFacilityModel->icon()->create([
                    ...$icon,
                    'media_role' => 'facility_icon'
                ]);
            }

            return $this->sendSuccess($subFacilityModel->load('facility_id', 'icon'), 'Sub Facility updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('SubFacility update failed.', [$e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubFacilityRepository $subFacilityRepository, string $subFacility): JsonResponse
    {
        try {
            $subFacilityModel = $subFacilityRepository->getModel($subFacility);

            if ($subFacilityModel->icon()->exists()) {
                $this->deleteFile($subFacilityModel->icon->name, 'facility_icons');
                $subFacilityModel->icon()->delete();
            }

            $subFacilityRepository->delete($subFacilityModel->id);

            return $this->sendSuccess(null, 'Sub Facility deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('SubFacility deletion failed.', [$e->getMessage()]);
        }
    }

    /**
     * Delete the icon associated with a sub facility.
     */
    private function deleteImage($subFacility): void
    {
        if ($subFacility->icon()->exists()) {
            $this->deleteFile($subFacility->icon->name, 'facility_icons');
            $subFacility->icon()->delete();
        }
    }
}
