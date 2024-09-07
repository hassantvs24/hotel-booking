<?php

namespace App\Http\Controllers\API\Admin\Location;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Place\StateRequest;
use App\Repositories\Admin\StateRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, StateRepository $stateRepository): JsonResponse
    {
        $userQuery = array_merge($request->only([
            'search',
            'order_by',
            'order_type',
            'filters',
            'page',
            'per_page'
        ]),
            [
                'order' => 'DESC',
                'with' => ['country']
            ]
        );

        $states = $stateRepository->paginate($userQuery);

        $data = [
            'states' => $states
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
    public function store(StateRequest $request, StateRepository $stateRepository): JsonResponse
    {
        try {
            $state = $stateRepository->create($request->validated());

            return $this->sendSuccess($state, 'State created successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'State creation failed.',
                (array)$e->getMessage()
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
    public function update(StateRequest $request, StateRepository $stateRepository, $state): JsonResponse
    {
        try {
            $state = $stateRepository->getModel($state);
            $state = $stateRepository->update($request->validated(), $state);

            return $this->sendSuccess($state, 'State updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'State update failed.',
                (array)$e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StateRepository $stateRepository, $state): JsonResponse
    {
        try {
            $state = $stateRepository->getModel($state);
            $stateRepository->delete($state->id);

            return $this->sendSuccess(null, 'State deleted successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'State deletion failed.',
                (array)$e->getMessage()
            );
        }
    }

    public function all(StateRepository $stateRepository): JsonResponse
    {
        $states = $stateRepository->get();

        $data = [
            'states' => $states
        ];

        return $this->sendSuccess($data);
    }
}
