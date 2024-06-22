<?php

namespace App\Http\Controllers\Admin\Place;

use App\Http\Controllers\BaseController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\Admin\Place\StateRequest;
use App\Repositories\Place\CountryRepository;
use App\Repositories\Place\StateRepository;
use App\Models\State;
use App\Models\Country;


class StateController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, StateRepository $stateRepository) :View
    {
        if (!hasPermission('can_view_state')) {
            $this->unauthorized();
        }

        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => [],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );

        $states = $stateRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_state',
            'create' => 'can_create_state',
            'update' => 'can_update_state',
            'delete' => 'can_delete_state',
        ];

        return view('admin.place.state.index', compact('states', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CountryRepository $countryRepository) :View
    {
        if (!hasPermission('can_create_state')) {
            $this->unauthorized();
        }

        $countries=$countryRepository->pluck('name','id')->toArray();
        return view('admin.place.state.create',compact('countries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StateRequest $request, StateRepository $stateRepository) : RedirectResponse
    {
        if (!hasPermission('can_create_state')) {
            $this->unauthorized();
        }
        try {
            $stateRepository->create($request->validated());
            return redirect()->route('admin.places.states.index')->with([
                'message'    => 'State added successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('notification', [
                [ 'type' => 'error', 'message' => 'State could not be created' ]
            ]);
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
    public function edit(State $state) : View
    {
        if (!hasPermission('can_update_state')) {
            $this->unauthorized();
        }

        $countries = Country::query()->pluck('name', 'id')->toArray();
        return view('admin.place.state.edit',compact('state','countries'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StateRequest $request, StateRepository $stateRepository, $state) :RedirectResponse
    {

        if (!hasPermission('can_update_state')) {
            $this->unauthorized();
        }

        try {
            $state= $stateRepository->getModel($state);
            $stateRepository->update($request->validated(), $state);

            return redirect()->route('admin.places.states.index')->with([
                'message'    => 'State updated successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something went wrong.',
                'alert-type' => 'error'
            ]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StateRepository $stateRepository, $state) : RedirectResponse
    {
        if (!hasPermission('can_delete_state')) {
            $this->unauthorized();
        }

        try {

            $state = $stateRepository->getModel($state);

            $stateRepository->delete($state->id);
            return redirect()->route('admin.places.states.index')->with([
                'message'    => 'State deleted successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something Went Wrong',
                'alert-type' => 'error'
            ]);
        }
    }
}
