<?php

namespace App\Http\Controllers\API\Admin\ACL;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Acl\RoleRequest;
use App\Repositories\Admin\RoleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleController extends BaseController
{
  /**
   * Display a listing of the resource.
   */
  public function index (Request $request, RoleRepository $roleRepository): JsonResponse
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
			'with' => ['permissions'],
		]);
	$roles = $roleRepository->paginate($userQuery);

	$data = [
		'roles' => $roles
	];

	return $this->sendSuccess($data);
  }

  /**
   * @param RoleRepository $roleRepository
   * @return JsonResponse
   */
  public function all (RoleRepository $roleRepository): JsonResponse
  {
	$roles = $roleRepository->get();

	$data = [
		'roles' => $roles
	];

	return $this->sendSuccess($data);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store (RoleRequest $request, RoleRepository $roleRepository): JsonResponse
  {
	try {
	  DB::beginTransaction();

	  $role = $roleRepository->create($request->validated());
	  $role->permissions()->sync($request->permissions);
	  $role->load('permissions');

	  DB::commit();

	  return $this->sendSuccess($role, 'Role created successfully.');

	} catch (\Exception $e) {
	  DB::rollBack();
	  return $this->sendError(
		  'Role creation failed.',
		  (array)$e->getMessage()
	  );
	}
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create ()
  {
	//
  }

  /**
   * Display the specified resource.
   */
  public function show (string $id)
  {
	//
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit (string $id)
  {
	//
  }

  /**
   * Update the specified resource in storage.
   */
  public function update (RoleRequest $request, RoleRepository $roleRepository, $role): JsonResponse
  {
	try {
	  DB::beginTransaction();

      $role = $roleRepository->getModel($role);

	  $role = $roleRepository->update($request->validated(), $role);
	  $role->permissions()->sync($request->permissions);
	  $role->load('permissions');

	  DB::commit();

	  return $this->sendSuccess($role, 'Role updated successfully.');

	} catch (\Exception $e) {
	  DB::rollBack();
	  return $this->sendError(
		  'Role update failed.',
		  (array)$e->getMessage()
	  );
	}
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy (RoleRepository $roleRepository, Role $role): JsonResponse
  {
	try {
	  DB::beginTransaction();

	  $role = $roleRepository->find($role->id);

	  if (!$role) {
		return $this->sendError('Role not found.');
	  }

	  $role->permissions()->sync([]);
	  DB::table('roles')->where('id', $role->id)->delete();

	  DB::commit();

	  return $this->sendSuccess([], 'Role deleted successfully.');

	} catch (\Exception $e) {
	  DB::rollBack();
	  return $this->sendError(
		  'Role deletion failed.',
		  (array)$e->getMessage()
	  );
	}
  }
}
