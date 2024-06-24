<?php

namespace App\Http\Controllers\Admin\ACL;

use App\Http\Controllers\BaseController;
use App\Repositories\Permission\PermissionRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;


class PermissionController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PermissionRepository $permissionRepository) : View
    {
        if (!hasPermission('can_view_acl_permission')) {
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

        $permissions = $permissionRepository->paginate($userQuery);
        return view('admin.acl.permission.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        if (!hasPermission('can_create_acl_permission')) {
            $this->unauthorized();
        }

        return view('admin.acl.permission.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) : RedirectResponse
    {
        if (!hasPermission('can_create_acl_permission')) {
            $this->unauthorized();
        }

        return redirect()->route('permissions.index');
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
        if (!hasPermission('can_update_acl_permission')) {
            $this->unauthorized();
        }

        return view('admin.acl.permission.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) : RedirectResponse
    {
        if (!hasPermission('can_update_acl_permission')) {
            $this->unauthorized();
        }

        return redirect()->route('permissions.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) : RedirectResponse
    {
        if (!hasPermission('can_delete_acl_permission')) {
            $this->unauthorized();
        }

        return redirect()->route('permissions.index');
    }
}
