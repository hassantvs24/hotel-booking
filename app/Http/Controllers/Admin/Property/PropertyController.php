<?php

namespace App\Http\Controllers\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Property\PropertyRequest;
use App\Models\Place;
use App\Models\Property;
use App\Repositories\Place\PlaceRepository;
use App\Repositories\Property\PropertyCategoryRepository;
use App\Repositories\Property\PropertyRepository;
use App\Repositories\User\UserRepository;
use App\Traits\MediaMan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PropertyController extends BaseController
{
    use MediaMan;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PropertyRepository $propertyRepository): View
    {
        if (!hasPermission('can_view_property')) {
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

        $properties = $propertyRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_property',
            'create' => 'can_create_property',
            'update' => 'can_update_property',
            'delete' => 'can_delete_property',
        ];

        return view('admin.property.property.index', compact('properties', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(PropertyCategoryRepository $propertyCategoryRepository, PlaceRepository $placeRepository, UserRepository $userRepository): View
    {
        if (!hasPermission('can_create_property')) {
            $this->unauthorized();
        }

        $propertyClasses = [
            'Unrated' => 'Unrated',
            '1 Star' => '1 Star',
            '2 Stars' => '2 Stars',
            '3 Stars' => '3 Stars',
            '4 Stars' => '4 Stars',
            '5 Stars' => '5 Stars',
            '6 Stars' => '6 Stars',
            '7 Stars' => '7 Stars',
        ];

        $status = [
            'Pending' => 'Pending',
            'Published' => 'Published',
            'Unpublished' => 'Unpublished'
        ];

        $propertyCategories = $propertyCategoryRepository->pluck('name', 'id')->toArray();
        $places = $placeRepository->pluck('name', 'id')->toArray();
        $users = $userRepository->pluck('name', 'id')->toArray();

        return view('admin.property.property.create', compact('propertyClasses', 'status', 'propertyCategories', 'places', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyRequest $request, PropertyRepository $propertyRepository): RedirectResponse
    {
        if (!hasPermission('can_create_property')) {
            $this->unauthorized();
        }

        try {
            $property = $propertyRepository->create(array_merge(
                $request->validated(),
                ['user_id' => Auth::user()->id]
            ));

            if ($request->hasFile('photo')) {
                $image = $this->storeFile($request->file('photo'), 'properties');
                $property->primaryImage()->create([...$image, 'media_role' => 'property_image']);
            }

            return redirect()->route('admin.properties.index')->with([
                'message'    => 'Property created successfully.',
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
    public function edit(PropertyCategoryRepository $propertyCategoryRepository, PlaceRepository $placeRepository, UserRepository $userRepository, Property $property): view
    {
        if (!hasPermission('can_update_property')) {
            $this->unauthorized();
        }
        $propertyClasses = [
            'Unrated' => 'Unrated',
            '1 Star' => '1 Star',
            '2 Stars' => '2 Stars',
            '3 Stars' => '3 Stars',
            '4 Stars' => '4 Stars',
            '5 Stars' => '5 Stars',
            '6 Stars' => '6 Stars',
            '7 Stars' => '7 Stars',
        ];
        $status = [
            'Pending' => 'Pending',
            'Published' => 'Published',
            'Unpublished' => 'Unpublished'
        ];

        $propertyCategories = $propertyCategoryRepository->pluck('name', 'id')->toArray();
        $places = $placeRepository->pluck('name', 'id')->toArray();
        $users = $userRepository->pluck('name', 'id')->toArray();

        return view('admin.property.property.edit', compact('propertyClasses', 'status', 'propertyCategories', 'places', 'users', 'property'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PropertyRequest $request, PropertyRepository $propertyRepository, $property): RedirectResponse
    {
        if (!hasPermission('can_update_property')) {
            $this->unauthorized();
        }
        try {

            $property = $propertyRepository->getModel($property);
            $propertyRepository->update(array_merge(
                $request->validated(),
                ['user_id' => Auth::user()->id]
            ), $property);
            if ($request->hasFile('photo')) {

                if ($property->primaryImage) {
                    $this->deleteFile($property->primaryImage->name, 'properties');
                    $property->primaryImage()->delete();
                }

                $image = $this->storeFile($request->file('photo'), 'properties');
                $property->primaryImage()->create([...$image, 'media_role' => 'property_image']);
            }

            return redirect()->route('admin.properties.index')->with([
                'message'    => 'Property updated successfully.',
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
    public function destroy(PropertyRepository $propertyRepository, $property): RedirectResponse
    {
        if (!hasPermission('can_delete_property')) {
            $this->unauthorized();
        }
        try {
            $property = $propertyRepository->getModel($property);
            $propertyRepository->delete($property->id);

            return redirect()->route('admin.properties.index')->with([
                'message'    => 'Property deleted successfully.',
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
