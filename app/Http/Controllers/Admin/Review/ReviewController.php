<?php

namespace App\Http\Controllers\Admin\Review;

use App\Http\Controllers\BaseController;
use App\Http\Requests\admin\Review\ReviewRequest;
use App\Models\Review;
use App\Repositories\Property\PropertyRepository;
use App\Repositories\Review\ReviewCategoryRepository;
use App\Repositories\Review\ReviewRepositroy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ReviewRepositroy $reviewRepositroy): View
    {
        if (!hasPermission('can_view_review')) {
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

        $reviews = $reviewRepositroy->paginate($query);

        $permissions = [
            'manage' => 'can_view_review',
            'create' => 'can_create_review',
            'update' => 'can_update_review',
            'delete' => 'can_delete_review',
        ];

        return view('admin.review.review.index', compact('reviews', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ReviewCategoryRepository $reviewCategoryRepository, PropertyRepository $propertyRepository)
    {
        if (!hasPermission('can_create_review')) {
            $this->unauthorized();
        }
        $reviewCategories = $reviewCategoryRepository->pluck('name', 'id')->toArray();
        $properties = $propertyRepository->pluck('name', 'id')->toArray();

        return view('admin.review.review.create', compact('reviewCategories', 'properties'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReviewRequest $request, ReviewRepositroy $reviewRepositroy)
    {
        if (!hasPermission('can_create_review')) {
            $this->unauthorized();
        }
        try {
            $reviewRepositroy->create(array_merge($request->validated(), ['user_id' => Auth::user()->id]));

            return redirect()->route('admin.reviews.index')->with([
                'message'    => 'Reviews created successfully.',
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
    public function edit(ReviewCategoryRepository $reviewCategoryRepository, PropertyRepository $propertyRepository, Review $review)
    {
        if (!hasPermission('can_update_review')) {
            $this->unauthorized();
        }

        $reviewCategories = $reviewCategoryRepository->pluck('name', 'id')->toArray();
        $properties = $propertyRepository->pluck('name', 'id')->toArray();

        return view('admin.review.review.edit', compact('reviewCategories', 'properties', 'review'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ReviewRequest $request, ReviewRepositroy $reviewRepository, $review)
    {
        if (!hasPermission('can_update_review')) {
            $this->unauthorized();
        }

        try {
            $review = $reviewRepository->getModel($review);
            $reviewRepository->update(array_merge($request->validated(), ['user_id' => Auth::user()->id]), $review);

            return redirect()->route('admin.reviews.index')->with([
                'message'    => 'Review updated successfully.',
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
    public function destroy(ReviewRepositroy $reviewRepository, $review)
    {
        if (!hasPermission('can_delete_review')) {
            $this->unauthorized();
        }

        try {
            $review = $reviewRepository->getModel($review);
            $reviewRepository->delete($review->id);

            return redirect()->route('admin.reviews.index')->with([
                'message'    => 'Review deleted successfully.',
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
