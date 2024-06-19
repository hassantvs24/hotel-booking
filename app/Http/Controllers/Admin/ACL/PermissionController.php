<?php

namespace App\Http\Controllers\Admin\ACL;

use App\Http\Controllers\BaseController;
use App\Repositories\Permission\PermissionRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Permission;


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
    public function create()
    {
        if (!hasPermission('can_create_acl_permission')) {
            $this->unauthorized();
        }

        //return view('admin.acl.permission.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!hasPermission('can_create_acl_permission')) {
            $this->unauthorized();
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
    public function edit(string $id)
    {
        if (!hasPermission('can_update_acl_permission')) {
            $this->unauthorized();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!hasPermission('can_update_acl_permission')) {
            $this->unauthorized();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!hasPermission('can_delete_acl_permission')) {
            $this->unauthorized();
        }
    }
}
