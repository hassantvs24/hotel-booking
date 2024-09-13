<?php

namespace App\Http\Controllers\API\Admin\Review;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Review\ReviewRequest;
use App\Repositories\Property\PropertyRepository;
use App\Repositories\Review\ReviewCategoryRepository;
use App\Repositories\Review\ReviewRepositroy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ReviewRepositroy $reviewRepository): JsonResponse
    {

        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => ['reviewCategory', 'property', 'user'],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );

        $reviews = $reviewRepository->paginate($query);

        return $this->sendSuccess(['reviews' => $reviews]);
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
    public function store(ReviewRequest $request, ReviewRepositroy $reviewRepository): JsonResponse
    {
        try {

            $reviewData = array_merge(
                $request->validated(),
                ['user_id' => Auth::user()->id]
            );

            $reviews = $reviewRepository->create($reviewData);

            $reviews->load('reviewCategory', 'property', 'user');

            return $this->sendSuccess($reviews, 'Review Created Successfully');
        } catch (\Exception $e) {

            return $this->sendError('Error creating Reviews.', [$e->getMessage()]);;
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
    public function update(ReviewRequest $request, ReviewRepositroy $reviewRepository, $review): JsonResponse
    {
        try {
            $reviews = $reviewRepository->getModel($review);
            $reviewRepository->update(array_merge(
                $request->validated(),
                ['user_id' => Auth::user()->id]
            ), $reviews);

            $reviews->load(['reviewCategory', 'property', 'user']);

            return $this->sendSuccess($reviews, 'Review updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Review update failed.', [$e->getMessage()]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReviewRepositroy $reviewRepository, $review): JsonResponse
    {


        try {
            $reviews = $reviewRepository->getModel($review);
            $reviewRepository->delete($reviews->id);

            return $this->sendSuccess(null, 'Review deleted successfully.');
        } catch (\Exception $e) {

            return $this->sendError('Review deletion failed.', [$e->getMessage()]);
        }
    }

    public function all(ReviewRepositroy $reviewRepository): JsonResponse
    {
        $reviews = $reviewRepository->get();

        $data = [
            'reviews' => $reviews
        ];

        return $this->sendSuccess($data);
    }
}
