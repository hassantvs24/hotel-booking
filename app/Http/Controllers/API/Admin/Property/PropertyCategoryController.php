<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Property\PropertyCategoryRequest;
use App\Repositories\Property\PropertyCategoryRepository;
use App\Traits\MediaMan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class PropertyCategoryController extends BaseController
{
    use MediaMan;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PropertyCategoryRepository $categoryRepository) : JsonResponse
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

        $propertyCategories = $categoryRepository->paginate($query);

        return $this->sendSuccess(['propertyCategories' => $propertyCategories]);

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
    public function store(
        PropertyCategoryRequest $request,PropertyCategoryRepository $categoryRepository) : JsonResponse {
        
        try {
            $propertyCategories = $categoryRepository->create($request->only(['name', 'notes']));

            if ($request->hasFile('icon')) {

                $icon = $this->storeFile(
                    $request->file('icon'),
                    'property_categories'
                );
                $propertyCategories->icon()->create([
                    ...$icon,
                    'media_role' => 'property_icon'
                ]);
            }

            return $this->sendSuccess($propertyCategories->load('icon'), 'Property Category created successfully.');

        } 
        catch (\Exception $e) {

            return $this->sendError('Property Category creation failed.',(array)$e->getMessage());
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
        PropertyCategoryRequest $request,PropertyCategoryRepository $categoryRepository,$propertyCategories) : JsonResponse {
        
            try {

            $propertyCategories = $categoryRepository->getModel($propertyCategories);

            $categoryRepository->update($request->only(['name', 'notes']),$propertyCategories);
            
            if (!$request->hasFile('icon') && $request->input('remove_icon')) {
                $this->deleteImage($propertyCategories);
            }
            if ($request->hasFile('icon')) {

                if ($propertyCategories->icon()->exists()) {
                    $this->deleteFile($propertyCategories->icon->name, 'property_icon');
                    $propertyCategories->icon()->delete();
                }

                $icon = $this->storeFile($request->file('icon'),'property_categories');
               
                $propertyCategories->icon()->create([
                    ...$icon,

                    'media_role' => 'property_icon'
                ]);
            }

            return $this->sendSuccess($propertyCategories->load('icon'), 'Property Category updated successfully.');

       } catch (\Exception $e) {
        
        return $this->sendError(
            'Property update failed.',
            (array)$e->getMessage()
        );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyCategoryRepository $categoryRepository, $propertyCategories) : JsonResponse
    {
    
        try {

            $model = $categoryRepository->getModel($propertyCategories);

            if ($model->icon()->exists()) {
                $this->deleteFile($model->icon->name, 'property_icon');
                $model->icon()->delete();
            }

            $categoryRepository->delete($model->id);

            return $this->sendSuccess(null, 'Property Category deleted successfully.');

        } catch (\Exception $e) {
            return $this->sendError(
                'Property deletion failed.',
                (array)$e->getMessage()
            );
        }
    }
    private function deleteImage($propertyCategories) : void
    {
        if ($propertyCategories->icon()->exists()) {
            $this->deleteFile($propertyCategories->icon->name, 'property_icon');
            $propertyCategories->icon()->delete();
        }
    }
}
