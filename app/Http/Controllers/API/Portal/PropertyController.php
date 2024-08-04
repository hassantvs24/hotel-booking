<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends BaseController
{
    public function index() : JsonResponse
    {
        $properties = Property::query()
            ->with(['images', 'facilities', 'place.city'])
            ->where('status', Property::STATUS_PUBLISHED)
            ->get();

        $data = [
            'properties' => $properties
        ];

        return $this->sendSuccess($data);
    }
}
