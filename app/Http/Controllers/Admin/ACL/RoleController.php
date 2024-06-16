<?php

namespace App\Http\Controllers\Admin\ACL;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Role;
use App\Http\Requests\Admin\ACL\RoleRequest;
use Illuminate\Http\RedirectResponse;


class RoleController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index() : View
    {

        $roles = Role::orderBy('id', 'desc')->paginate(10);
        return view('admin.acl.role.index',compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() :View
    {
        return view('admin.acl.role.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request) :RedirectResponse
    {
        try {
            $requestValidated = $request->validated();

            $data=Role::create([
                'name' => $requestValidated['name'],
                'description' => $requestValidated['description'],
            ]);

            return redirect()->route('admin.acl.roles.index')->with('success', 'Role added successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong');
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
    public function edit(Role $role) :View
    {
        return view('admin.acl.role.edit',compact('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role) :RedirectResponse
    {
        try {
            $role->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            return redirect()->route('admin.acl.roles.index')->with('success', 'Roles updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something Went Wrong');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id) :RedirectResponse
    {
        Role::find($request->id)->delete();
    }
}
