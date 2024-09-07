<?php

namespace App\Http\Controllers\API\Admin\Location;

use App\Http\Controllers\BaseController;
use App\Repositories\Admin\CountryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, CountryRepository $countryRepository) : JsonResponse
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
                'order' => 'ASC',
            ]
        );

        $countries = $countryRepository->paginate($userQuery);

        $data = [
            'countries' => $countries
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
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function all (CountryRepository $countryRepository): JsonResponse
    {
        $countries = $countryRepository->get();

        $data = [
            'countries' => $countries
        ];

        return $this->sendSuccess($data);
    }
}
