<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Property\PropertyRequest;
use App\Models\Property;
use App\Repositories\Facility\SubFacilityRepository;
use App\Repositories\Place\PlaceRepository;
use App\Repositories\Property\PropertyCategoryRepository;
use App\Repositories\Property\PropertyRepository;
use App\Repositories\User\UserRepository;
use App\Traits\MediaMan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PropertyController extends BaseController
{
    use MediaMan;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PropertyRepository $propertyRepository): JsonResponse
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

        $properties = $propertyRepository->paginate($query);

        return $this->sendSuccess($properties);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyRequest $request, PropertyRepository $propertyRepository): JsonResponse
    {

        DB::beginTransaction();
        try {
            $property = $propertyRepository->create(
                array_merge(
                    $request->except(['photo', 'property_facilities']),
                    ['user_id' => Auth::auth()->id()]
                )
            );

            if (is_array($request->input('property_facilities'))) {
                $property->facilities()->attach($request->input('property_facilities'));
            }

            if ($request->hasFile('photo')) {
                $image = $this->storeFile($request->file('photo'), 'properties');
                $property->primaryImage()->create([...$image, 'media_role' => 'property_image']);
            }

            DB::commit();

            return $this->sendSuccess($property->load('facilities', 'primaryImage'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
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
    public function update(
        PropertyRequest $request,
        PropertyRepository $propertyRepository,
        $property
    ): JsonResponse {

        try {

            $property = $propertyRepository->getModel($property);
            $propertyRepository->update($request->except(['photo', 'property_facilities']), $property);

            if (is_array($request->input('property_facilities'))) {
                $property->facilities()->sync($request->input('property_facilities'));
            }

            if ($request->hasFile('photo')) {

                $this->deleteImage($property);

                $image = $this->storeFile($request->file('photo'), 'properties');
                $property->primaryImage()->create([...$image, 'media_role' => 'property_image']);
            }

            return $this->sendSuccess($property->load('facilities', 'primaryImage'));
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyRepository $propertyRepository, $property): JsonResponse
    {
        if (!hasPermission('can_delete_property')) {
            $this->unauthorized();
        }
        try {
            $property = $propertyRepository->getModel($property);
            $this->deleteImage($property);
            $propertyRepository->delete($property->id);

            return $this->sendSuccess([], 'Property deleted sucessfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function all(PropertyRepository $propertyRepository): JsonResponse
    {
        $properties = $propertyRepository->get();

        $data = [
            'properties' => $properties
        ];

        return $this->sendSuccess($data);
    }


    private function deleteImage($property): void
    {
        if ($property->primaryImage->exists()) {
            $this->deleteFile($property->primaryImage->name, 'properties');
            $property->primaryImage()->delete();
        }
    }
}
