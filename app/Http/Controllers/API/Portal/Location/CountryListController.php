<?php

namespace App\Http\Controllers\API\Portal\Location;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;

/** The countries a guest can choose in their profile: Bangladesh first, then the rest in order. */
class CountryListController extends BaseController
{
    public function index(): JsonResponse
    {
        $first = config('countries.first', []);
        $rest  = array_values(array_diff(config('countries.all', []), $first));

        return $this->sendSuccess(['countries' => array_merge($first, $rest)])
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
