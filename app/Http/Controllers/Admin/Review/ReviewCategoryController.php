<?php

namespace App\Http\Controllers\Admin\Review;

use App\Http\Controllers\BaseController;
use App\Http\Requests\admin\Review\ReviewCategoryRequest;
use App\Repositories\Review\ReviewCategoryRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Namshi\JOSE\Signer\OpenSSL\RSA;

class ReviewCategoryController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request,ReviewCategoryRepository $reviewCategory): View
    {
        if (!hasPermission('can_view_review_category')) {
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

        $reviewCategories= $reviewCategory->paginate($query);

        $permissions = [
            'manage' => 'can_view_review_category',
            'create' => 'can_create_review_category',
            'update' => 'can_update_review_category',
            'delete' => 'can_delete_review_category',
        ];
        

        return view('admin.review.category.index', compact('reviewCategories', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!hasPermission('can_create_review_category')) {
            $this->unauthorized();
        }

        return view('admin.review.category.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReviewCategoryRequest $request, ReviewCategoryRepository $reviewRepository) :RedirectResponse
    {
        if (!hasPermission('can_create_review_category')) {
            $this->unauthorized();
        }

        try {
            $reviewRepository->create($request->validated());

            return redirect()->route('admin.reviews.categories.index')->with([
                'message'    => 'Review Category created successfully.',
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
    public function edit(ReviewCategoryRepository $reviewCategoryRepository, $reviewCategory)
    {
        if (!hasPermission('can_update_review_category')) {
            $this->unauthorized();
        }

        $reviewCategory = $reviewCategoryRepository->getModel($reviewCategory);

        return view('admin.review.category.edit', compact('reviewCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ReviewCategoryRequest $request,ReviewCategoryRepository $reviewCategoryRepository,$reviewCategory)
    {
        if (!hasPermission('can_update_review_category')) {
            $this->unauthorized();
        }

        try {

            $reviewCategory = $reviewCategoryRepository->getModel($reviewCategory);
            $reviewCategoryRepository->update($request->validated(), $reviewCategory);

            return redirect()->route('admin.reviews.categories.index')->with([
                'message'    => 'Review Category updated successfully.',
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
    public function destroy(ReviewCategoryRepository $reviewCategoryRepository, $reviewCategory)
    {
        if (!hasPermission('can_delete_review_category')) {
            $this->unauthorized();
        }

        try {

            $reviewCategoryRepository->delete($reviewCategory);

            return redirect()->route('admin.reviews.categories.index')->with([
                'message'    => 'Reviews Category deleted successfully.',
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
