<?php

namespace App\Http\Controllers\Admin\Surrounding;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Repositories\Surrounding\SurroundingRepository;
use App\Http\Requests\Admin\Surrounding\SurroundingRequest;
use App\Models\Surrounding;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurroundingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request,SurroundingRepository $surroundingRepository): View
    {
        if (!hasPermission('can_view_surrounding')) {
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

        $surroundings  = $surroundingRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_surrounding',
            'create' => 'can_create_surrounding',
            'update' => 'can_update_surrounding',
            'delete' => 'can_delete_surrounding',
        ];

        return view('admin.surrounding.surrounding.index', compact('surroundings', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        if (!hasPermission('can_create_surrounding')) {
            $this->unauthorized();
        }
        return view('admin.surrounding.surrounding.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SurroundingRequest $request, SurroundingRepository $surroundingRepository): RedirectResponse
    {
        if (!hasPermission('can_create_surrounding')) {
            $this->unauthorized();
        }
        try {
            $surroundingRepository->create($request->validated());

            return redirect()->route('admin.surroundings.index')->with([
                'message'    => 'Surrounding created successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something Went Wrong',
                'alert-type' => 'error'
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
    public function edit(Surrounding $surrounding): View
    {
        if (!hasPermission('can_update_surrounding')) {
            $this->unauthorized();
        }

        return view('admin.surrounding.surrounding.edit', compact('surrounding'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SurroundingRequest $request, SurroundingRepository $surroundingRepository,$surrounding): RedirectResponse
    {
        if (!hasPermission('can_update_surrounding')) {
            $this->unauthorized();
        }
        try {
            $surrounding =  $surroundingRepository->getModel($surrounding);
            $surroundingRepository->update($request->validated(),$surrounding);
            return redirect()->route('admin.surroundings.index')->with([
                'message'    => 'Surrounding updated successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something Went Wrong',
                'alert-type' => 'error'
            ]);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SurroundingRepository $surroundingRepository,$surrounding): RedirectResponse
    {
        if (!hasPermission('can_delete_surrounding')) {
            $this->unauthorized();
        }

        try {

            $surrounding = $surroundingRepository->getModel($surrounding);
            $surroundingRepository->delete($surrounding->id);

            return redirect()->route('admin.surroundings.index')->with([
                'message'    => 'Surrounding deleted successfully.',
                'alert-type' => 'success'
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something went wrong.',
                'alert-type' => 'error'
            ]);
        }
    }
}
