<?php

namespace App\Http\Controllers\Admin\ACL;

use App\Http\Controllers\BaseController;
use App\Repositories\Role\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\Admin\ACL\RoleRequest;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class RoleController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, RoleRepository $roleRepository) : View
    {
        $userQuery = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => [],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );

        $roles = $roleRepository->paginate($userQuery);
        return view('admin.acl.role.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        $permissions = Permission::query()->pluck('name', 'id')->toArray();
        return view('admin.acl.role.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request, RoleRepository $roleRepository) : RedirectResponse
    {
        try {

            $role = $roleRepository->create(
                $request->only(['name', 'description'])
            );
            $role->permissions()->attach($request->permissions);

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
    public function edit(Role $role) : View
    {
        $permissions = Permission::query()->pluck('name', 'id')->toArray();
        return view('admin.acl.role.edit', compact('role', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, RoleRepository $roleRepository, $role) : RedirectResponse
    {
        try {
            $role = $roleRepository->getModel($role);
            $roleRepository->update(
                $request->only(['name', 'description']),
                $role
            );

            $role->permissions()->sync($request->permissions);

            return redirect()->route('admin.acl.roles.index')->with('success', 'Roles updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something Went Wrong');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoleRepository $roleRepository, $role) : RedirectResponse
    {
        try {

            $roleModel = $roleRepository->getModel($role);

            $roleModel->users()->detach();
            $roleModel->permissions()->detach();
            $roleRepository->delete($role);

            return redirect()->route('admin.acl.roles.index')->with('success', 'Role deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something Went Wrong');
        }
    }
}
