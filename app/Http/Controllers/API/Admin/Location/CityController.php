<?php

namespace App\Http\Controllers\API\Admin\Location;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Place\CityRequest;
use App\Repositories\Admin\CityRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, CityRepository $cityRepository): JsonResponse
    {
        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with' => ['state'],
                'where' => [],
                'order_by' => 'id',
                'order' => 'DESC',
            ]
        );

        $cities = $cityRepository->paginate($query);

        $data = [
            'cities' => $cities
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
    public function store(CityRequest $request, CityRepository $cityRepository): JsonResponse
    {
        try {
            $city = $cityRepository->create($request->validated());

            return $this->sendSuccess($city, 'City created successfully');

        } catch (Exception $e) {
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
    public function update(CityRequest $request, CityRepository $cityRepository, $city): JsonResponse
    {
        try {
            $city = $cityRepository->getModel($city);
            $city = $cityRepository->update($request->validated(), $city);

            return $this->sendSuccess($city, 'City updated successfully');

        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CityRepository $cityRepository, $city): JsonResponse
    {
        try {
            $city = $cityRepository->getModel($city);
            $cityRepository->delete($city->id);

            return $this->sendSuccess(null, 'City deleted successfully');

        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function all(CityRepository $cityRepository): JsonResponse
    {
        $cities = $cityRepository->get();

        $data = [
            'cities' => $cities
        ];

        return $this->sendSuccess($data);
    }
}
