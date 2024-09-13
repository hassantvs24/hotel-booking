<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Property\PropertyRuleRequest;
use App\Repositories\Property\PropertyRuleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class PropertyRuleController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PropertyRuleRepository $ruleRepository): JsonResponse
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

        $propertyRules = $ruleRepository->paginate($query);

    
        return $this->sendSuccess(['propertyRules' => $propertyRules]);
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
    public function store(PropertyRuleRequest $request, PropertyRuleRepository $ruleRepository): JsonResponse
    {
        try {
            $ruleRepository->create($request->validated());

           return $this->sendSuccess($ruleRepository, 'Property Rules created successfully.');
        }
         catch (\Exception $e) {
            return $this->sendError(
                'Property Rules creation failed.', (array)$e->getMessage()
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
    public function update(PropertyRuleRequest $request, PropertyRuleRepository $propertyRuleRepository, $propertyRule): JsonResponse
    {
      
         try {
        $propertyRule = $propertyRuleRepository->getModel($propertyRule);
        $propertyRuleRepository->update($request->validated(), $propertyRule);

        return $this->sendSuccess($propertyRule, 'Property Rules update successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Property Rules update failed.', (array)$e->getMessage()
            );
       }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyRuleRepository $propertyRuleRepository, $propertyRule): JsonResponse
    {
        try {
            $propertyRule = $propertyRuleRepository->getModel($propertyRule);
            $propertyRuleRepository->delete($propertyRule->id);

       
            return $this->sendSuccess($propertyRule, 'Property Rules deletion successfully.');
        } 
        catch (\Exception $e) {

           return $this->sendError('Property Rules deletion failed.', (array)$e->getMessage());
        }
    }
}
