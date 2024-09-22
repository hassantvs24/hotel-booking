<?php

namespace App\Http\Controllers\API\Admin\Review;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Review\ReviewCategoryRequest;
use App\Repositories\Admin\ReviewCategoryRepository;
use App\Traits\MediaMan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewCategoryController extends BaseController
{

    use MediaMan;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ReviewCategoryRepository $reviewCategory): JsonResponse
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

        $reviewCategories = $reviewCategory->paginate($query);

        return $this->sendSuccess(['reviewCategories' => $reviewCategories]);
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
    public function store(ReviewCategoryRequest $request, ReviewCategoryRepository $reviewCategoryRepository): JsonResponse
    {
        try {
            $reviewCategories = $reviewCategoryRepository->create($request->only(['name', 'notes']));

            if ($request->hasFile('icon')) {
                $icon = $this->storeFile($request->file('icon'), 'review_icons');
                $reviewCategories->icon()->create([
                    ...$icon,
                    'media_role' => 'review_icon'
                ]);
            }

            return $this->sendSuccess($reviewCategories->load('icon'), 'Review Category created successfully.');
        }
        catch (\Exception $e) {

            return $this->sendError(
                'Review Category creation failed.', (array)$e->getMessage()
            );
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
    public function update(ReviewCategoryRequest $request, ReviewCategoryRepository $reviewCategoryRepository, $reviewCategory) : JsonResponse
    {
        try {

            $reviewCategory = $reviewCategoryRepository->getModel($reviewCategory);
            $reviewCategories = $reviewCategoryRepository->update($request->only(['name', 'notes']), $reviewCategory);

            if (!$request->hasFile('icon') && $request->input('remove_icon')) {
                $this->deleteImage($reviewCategories);
            }
            if ($request->hasFile('icon')) {

                if ($reviewCategories->icon()->exists()) {
                    $this->deleteFile($reviewCategories->icon->name, 'review_icons');
                    $reviewCategories->icon()->delete();
                }

                $icon = $this->storeFile($request->file('icon'), 'review_icons');
                $reviewCategories->icon()->create([
                    ...$icon,
                    'media_role' => 'review_icon'
                ]);
            }

            return $this->sendSuccess($reviewCategories->load('icon'), 'ReviewCategory updated successfully.');

        } catch (\Exception $e) {

            return $this->sendError('Review Category update failed.',(array)$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReviewCategoryRepository $reviewCategoryRepository, $reviewCategory):JsonResponse
    {
        try {
            $reviewCategory = $reviewCategoryRepository->getModel($reviewCategory);

            if ($reviewCategory->icon()->exists()) {
                $this->deleteFile($reviewCategory->icon->name, 'review_icons');
                $reviewCategory->icon()->delete();
            }

            $reviewCategoryRepository->delete($reviewCategory->id);

            return $this->sendSuccess(null, 'Review Category deleted successfully.');

        } catch (\Exception $e) {

            return $this->sendError('Review Category deletion failed',(array)$e->getMessage());
        }
    }
    private function deleteImage($reviewCategories) : void
    {
        if ($reviewCategories->icon()->exists()) {
            $this->deleteFile($reviewCategories->icon->name, 'review_icons');
            $reviewCategories->icon()->delete();
        }
    }
}
