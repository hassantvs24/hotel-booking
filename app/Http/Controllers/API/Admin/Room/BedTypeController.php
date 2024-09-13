<?php

namespace App\Http\Controllers\API\Admin\Room;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Room\BedTypeRequest;
use App\Repositories\Admin\BedTypeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BedTypeController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, BedTypeRepository $bedTypeRepository):JsonResponse
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

        $bedTypes = $bedTypeRepository->paginate($query);


        return $this->sendSuccess(['bedTypes' => $bedTypes]);
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
    public function store(BedTypeRequest $request, BedTypeRepository $bedTypeRepository):JsonResponse
    {
        try {
            $bedTypeRepository->create($request->validated());

            return $this->sendSuccess($bedTypeRepository, 'Bed Type created successfully.');

        }
        catch (\Exception $e) {

            return $this->sendError('Bed Type creation failed.', (array)$e->getMessage());

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
    public function update(BedTypeRequest $request, BedTypeRepository $bedTypeRepository, $bedType):JsonResponse
    {
        try {
            $bedType = $bedTypeRepository->getModel($bedType);
            $bedTypeRepository->update($request->validated(), $bedType);

            return $this->sendSuccess($bedType, 'Bed Type update successfully.');

        } catch (\Exception $e) {

            return $this->sendError('Bed Type update failed.', (array)$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BedTypeRepository $bedTypeRepository, $bedType): JsonResponse
    {
        try {
            $bedType = $bedTypeRepository->getModel($bedType);
            $bedTypeRepository->delete($bedType->id);

            return $this->sendSuccess($bedTypeRepository, 'Bed Type deletion successfully.');

        } catch (\Exception $e) {

            return $this->sendError('Bed Types deletion failed.', (array)$e->getMessage());
        }
    }
}
