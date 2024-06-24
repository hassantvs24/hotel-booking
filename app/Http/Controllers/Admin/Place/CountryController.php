<?php

namespace App\Http\Controllers\Admin\Place;

use App\Http\Controllers\BaseController;
use App\Repositories\Place\CountryRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CountryController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, CountryRepository $countryRepository) : View
    {
        if (!hasPermission('can_view_country')) {
            $this->unauthorized();
        }

        $countryQuery = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => [],
                'where'    => [],
                'order_by' => 'name',
                'order'    => 'ASC',
            ]
        );
        $countries = $countryRepository->paginate($countryQuery);

        return view('admin.place.country.index', compact('countries'));
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
}
