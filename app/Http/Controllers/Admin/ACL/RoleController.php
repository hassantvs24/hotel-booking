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
        if (!hasPermission('can_view_acl_role')) {
            $this->unauthorized();
        }

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

        $permissions = [
            'manage' => 'can_view_acl_role',
            'create' => 'can_create_acl_role',
            'update' => 'can_update_acl_role',
            'delete' => 'can_delete_acl_role',
        ];

        return view('admin.acl.role.index', compact('roles', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        if (!hasPermission('can_create_acl_role')) {
            $this->unauthorized();
        }

        $permissions = Permission::query()->pluck('name', 'id')->toArray();
        return view('admin.acl.role.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request, RoleRepository $roleRepository) : RedirectResponse
    {
        if (!hasPermission('can_create_acl_role')) {
            $this->unauthorized();
        }

        try {

            $role = $roleRepository->create(
                $request->only(['name', 'description'])
            );
            $role->permissions()->attach($request->permissions);

            return redirect()->route('admin.acl.roles.index')->with([
                'message'    => 'Role created successfully.',
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
    public function edit(Role $role) : View
    {
        if (!hasPermission('can_update_acl_role')) {
            $this->unauthorized();
        }

        $permissions = Permission::query()->pluck('name', 'id')->toArray();
        return view('admin.acl.role.edit', compact('role', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, RoleRepository $roleRepository, $role) : RedirectResponse
    {
        if (!hasPermission('can_update_acl_role')) {
            $this->unauthorized();
        }

        try {
            $role = $roleRepository->getModel($role);
            $roleRepository->update(
                $request->only(['name', 'description']),
                $role
            );

            $role->permissions()->sync($request->permissions);

            return redirect()->route('admin.acl.roles.index')->with([
                'message'    => 'Role updated successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something Went Wrong');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoleRepository $roleRepository, $role) : RedirectResponse
    {
        if (!hasPermission('can_delete_acl_role')) {
            $this->unauthorized();
        }

        try {

            $roleModel = $roleRepository->getModel($role);

            $roleModel->users()->detach();
            $roleModel->permissions()->detach();
            $roleRepository->delete($role);

            return redirect()->route('admin.acl.roles.index')->with([
                'message'    => 'Role deleted successfully.',
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
