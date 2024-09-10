<?php

namespace App\Http\Controllers\API\Admin\Facility;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Facility\FacilityRequest;
use App\Repositories\Admin\FacilityRepository;
use App\Traits\MediaMan;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityController extends BaseController
{
    use MediaMan;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, FacilityRepository $facilityRepository): JsonResponse
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

        $facilities = $facilityRepository->paginate($query);

        $data = [
            'facilities' => $facilities
        ];

        return $this->sendSuccess($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FacilityRequest $request, FacilityRepository $facilityRepository): JsonResponse
    {
        try {
            $facility = $facilityRepository->create($request->only(['name', 'notes', 'facility_type', 'facility_for']));

            if ($request->hasFile('icon')) {
                $icon = $this->storeFile($request->file('icon'), 'facility_icons');
                $facility->icon()->create([
                    ...$icon,
                    'media_role' => 'facility_icon'
                ]);
            }

            return $this->sendSuccess($facility->load('icon'), 'Facility created successfully.');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
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
    public function update(FacilityRequest $request, FacilityRepository $facilityRepository, $facility): JsonResponse
    {
        try {
            $facility = $facilityRepository->getModel($facility);
            $facilityRepository->update($request->only(['name', 'notes', 'facility_type', 'facility_for']), $facility);

            if (!$request->hasFile('icon') && $request->input('remove_icon')) {
                $this->deleteIcon($facility);
            }

            if ($request->hasFile('icon')) {

                $this->deleteIcon($facility);

                $icon = $this->storeFile($request->file('icon'), 'facility_icons');
                $facility->icon()->create([
                    ...$icon,
                    'media_role' => 'facility_icon'
                ]);
            }

            return $this->sendSuccess($facility->load('icon'), 'Facility updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FacilityRepository $facilityRepository, $facility): JsonResponse
    {
        try {

            $facility = $facilityRepository->getModel($facility);
            $this->deleteIcon($facility);
            $facilityRepository->delete($facility->id);

            return $this->sendSuccess([], 'Facility deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.');
        }
    }

    public function all(FacilityRepository $facilityRepository): JsonResponse
    {
        $facilities = $facilityRepository->get();

        $data = [
            'facilities' => $facilities
        ];

        return $this->sendSuccess($data);
    }

    private function deleteIcon($facility) : void
    {
        if ($facility->icon()->exists()) {
            $this->deleteFile($facility->icon->name, 'facility_icons');
            $facility->icon()->delete();
        }
    }
}
