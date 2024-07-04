<?php

namespace App\Http\Controllers\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Property\PropertyCategoryRequest;
use App\Repositories\Property\PropertyCategoryRepository;
use App\Traits\MediaMan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyCategoryController extends BaseController
{
    use MediaMan;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PropertyCategoryRepository $categoryRepository) : View
    {
        if (!hasPermission('can_view_property_category')) {
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

        $categories = $categoryRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_property_category',
            'create' => 'can_create_property_category',
            'update' => 'can_update_property_category',
            'delete' => 'can_delete_property_category',
        ];

        return view('admin.property.category.index', compact('categories', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        if (!hasPermission('can_create_property_category')) {
            $this->unauthorized();
        }

        return view('admin.property.category.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        PropertyCategoryRequest $request,
        PropertyCategoryRepository $categoryRepository
    ) : RedirectResponse {
        if (!hasPermission('can_create_property_category')) {
            $this->unauthorized();
        }

        try {
            $category = $categoryRepository->create($request->only(['name', 'notes']));

            if ($request->hasFile('icon')) {

                $image = $this->storeFile(
                    $request->file('icon'),
                    'property_categories'
                );
                $category->icon()->create([
                    ...$image,
                    'media_role' => 'property_icon'
                ]);
            }

            return redirect()->route('admin.properties.categories.index')->with([
                'message'    => 'Property Category created successfully.',
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
    public function edit(PropertyCategoryRepository $categoryRepository, $propertyCategory) : View
    {
        if (!hasPermission('can_update_property_category')) {
            $this->unauthorized();
        }

        $category = $categoryRepository->getModel($propertyCategory);

        return view('admin.property.category.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        PropertyCategoryRequest $request,
        PropertyCategoryRepository $categoryRepository,
        $propertyCategory
    ) : RedirectResponse {
        if (!hasPermission('can_update_property_category')) {
            $this->unauthorized();
        }

//        try {

            $propertyCategory = $categoryRepository->getModel($propertyCategory);
            $categoryRepository->update(
                $request->only(['name', 'notes']),
                $propertyCategory
            );

            if ($request->hasFile('icon')) {

                if ($propertyCategory->icon()->exists()) {
                    $this->deleteFile($propertyCategory->icon->name, 'property_icon');
                    $propertyCategory->icon()->delete();
                }

                $image = $this->storeFile(
                    $request->file('icon'),
                    'property_categories'
                );
                $propertyCategory->icon()->create([
                    ...$image,
                    'media_role' => 'property_icon'
                ]);
            }

            return redirect()->route('admin.properties.categories.index')->with([
                'message'    => 'Property Category updated successfully.',
                'alert-type' => 'success'
            ]);

//        } catch (\Exception $e) {
//            return redirect()->back()->with([
//                'message'    => 'Something went wrong.',
//                'alert-type' => 'error'
//            ]);
//        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyCategoryRepository $categoryRepository, $propertyCategory) : RedirectResponse
    {
        if (!hasPermission('can_delete_property_category')) {
            $this->unauthorized();
        }

        try {

            $model = $categoryRepository->getModel($propertyCategory);

            if ($model->icon()->exists()) {
                $this->deleteFile($model->icon->name, 'property_icon');
                $model->icon()->delete();
            }

            $categoryRepository->delete($model->id);

            return redirect()->route('admin.properties.categories.index')->with([
                'message'    => 'Property Category deleted successfully.',
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
