<?php

namespace App\Http\Controllers\API\Admin\Room;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Room\PriceTypeRequest;
use App\Repositories\Room\PriceTypeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class PriceTypeController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PriceTypeRepository $typeRepository) : JsonResponse
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

        $priceTypes= $typeRepository->paginate($query);

        
        return $this->sendSuccess(['priceTypes' => $priceTypes]);
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
    public function store( PriceTypeRequest $request, PriceTypeRepository $priceTypeRepository ):JsonResponse
    {

        try {
            $priceTypeRepository->create($request->validated());

            return $this->sendSuccess($priceTypeRepository, 'Price Type created successfully.');

        } 
        catch (\Exception $e) {
            
            return $this->sendError('Price Type creation failed.', (array)$e->getMessage());
        
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
    public function update(PriceTypeRequest $request, PriceTypeRepository $priceTypeRepository, $priceType) : JsonResponse
    {
        try {

            $priceType = $priceTypeRepository->getModel($priceType);
            $priceTypeRepository->update($request->validated(), $priceType);

            return $this->sendSuccess($priceType, 'Price Type update successfully.');

        } catch (\Exception $e) {

            return $this->sendError('Price Type update failed.', (array)$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PriceTypeRepository $priceTypeRepository, $priceType):JsonResponse
    {
        try {

            $priceTypeRepository->delete($priceType);

            return $this->sendSuccess($priceTypeRepository, 'Price Type deletion successfully.');

        } catch (\Exception $e) {

            return $this->sendError('Price Types deletion failed.', (array)$e->getMessage());
        }
    }
}
