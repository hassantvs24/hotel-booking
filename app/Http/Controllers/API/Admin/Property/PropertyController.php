<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Property\PropertyRequest;
use App\Models\Facility;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\PropertyRule;
use App\Models\User;
use App\Repositories\Admin\PropertyRepository;
use App\Traits\MediaMan;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Throwable;

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
                'with' => ['user'],
                'where' => [],
                'order_by' => 'id',
                'order' => 'DESC',
            ]
        );

        $properties = $propertyRepository->paginate($query);


        $data = [
            'properties' => $properties,
        ];

        return $this->sendSuccess($data);
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
                    ['user_id' => $request->user()->id]
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
        } catch (Exception $e) {
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
        PropertyRequest    $request,
        PropertyRepository $propertyRepository,
                           $property
    ): JsonResponse
    {

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
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyRepository $propertyRepository, $property): JsonResponse
    {
        try {
            $property = $propertyRepository->getModel($property);
            $this->deleteImage($property);
            $propertyRepository->delete($property->id);

            return $this->sendSuccess([], 'Property deleted successfully');
        } catch (Exception $e) {
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
        if ($property->primaryImage()->exists()) {
            $this->deleteFile($property->primaryImage->name, 'properties');
            $property->primaryImage()->delete();
        }
    }

    public function propertyAction(Property $property, Request $request): JsonResponse
    {
        $originalStatus = $property->status;
        $statusInput = $request->input('status');

        DB::beginTransaction();
        try {

            if ($originalStatus === $statusInput) {
                return $this->sendSuccess($property, 'Property status updated successfully');
            }

            $property->update([
                'status' => $statusInput
            ]);

            $propertyOwnerRoleName = config('site.hotelOwnerGroup');
            $propertyOwner = User::query()->where('id', $property->user_id)->first();
            $propertyOwnerRole = Role::query()->where('name', $propertyOwnerRoleName)->first();


            if ($statusInput === 'Published') {

                $existingRoles = $propertyOwner->roles()->whereNotNull('property_id')->get();

                if ($existingRoles->isNotEmpty()) {
                    foreach ($existingRoles as $role) {
                        $propertyOwner->removeRole($role);
                    }
                }

                if ($propertyOwnerRole) {
                    $propertyOwner->assignRole($propertyOwnerRole);
                }

            } else if ($statusInput === 'Unpublished') {
                if (!$propertyOwner->isAdmin) {
                    $propertyOwner->removeRole($propertyOwnerRoleName);
                }
            }

            DB::commit();
            return $this->sendSuccess($property, 'Property status updated successfully');

        } catch (Throwable $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
    }

    public function propertyAttributes(): JsonResponse
    {
        $categories = PropertyCategory::query()->get(['id', 'name']);
        $facilities = Facility::with([
            'subFacilities' => function ($query) {
                $query->select('id', 'name', 'facility_id');
            }
        ])
            ->whereHas('subFacilities')
            ->where('facility_for', 'Property')
            ->get(['id', 'name']);

        $propertyRules = PropertyRule::query()->get();

        $data = [
            'categories' => $categories,
            'facilities' => $facilities,
            'property_rules' => $propertyRules
        ];

        return $this->sendSuccess($data);
    }
}
