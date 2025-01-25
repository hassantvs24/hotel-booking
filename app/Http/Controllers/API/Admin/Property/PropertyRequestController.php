<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Repositories\Admin\PropertyRequestRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyRequestController extends BaseController
{
    public function index(Request $request, PropertyRequestRepository $propertyRequestRepository) : JsonResponse
    {
        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with' => [],
                'where' => [],
                'order_by' => 'id',
                'order' => 'DESC',
            ]
        );

        $propertyRequests = $propertyRequestRepository->paginate($query);

        return $this->sendSuccess(['property_requests' => $propertyRequests]);
    }
}
