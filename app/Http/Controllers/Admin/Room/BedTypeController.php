<?php

namespace App\Http\Controllers\Admin\Room;

use App\Http\Controllers\BaseController;
use App\Repositories\Room\BedTypeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BedTypeController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, BedTypeRepository $typeRepository) : View
    {
        if (!hasPermission('can_view_bed_type')) {
            $this->unauthorized();
        }

        return view('admin.room.bed-type.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        if (!hasPermission('can_create_bed_type')) {
            $this->unauthorized();
        }

        return view('admin.room.bed-type.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) : RedirectResponse
    {
        if (!hasPermission('can_create_bed_type')) {
            $this->unauthorized();
        }

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
    public function edit(string $id) : View
    {
        if (!hasPermission('can_edit_bed_type')) {
            $this->unauthorized();
        }

        return view('admin.room.bed-type.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) : RedirectResponse
    {
        if (!hasPermission('can_edit_bed_type')) {
            $this->unauthorized();
        }

        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) : RedirectResponse
    {
        if (!hasPermission('can_delete_bed_type')) {
            $this->unauthorized();
        }

        //
    }
}
