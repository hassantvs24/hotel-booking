<?php

namespace App\Http\Controllers\API\Admin\ACL;

use App\Http\Controllers\BaseController;
use App\Repositories\Admin\PermissionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends BaseController
{
  /**
   * Display a listing of the resource.
   */
  public function index (Request $request, PermissionRepository $permissionRepository): JsonResponse
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
			'order' => 'ASC',
		]
	);
	$permissions = $permissionRepository->paginate($userQuery);

	$data = [
		'permissions' => $permissions
	];

	return $this->sendSuccess($data);
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create ()
  {
	//
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store (Request $request)
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
  public function update (Request $request, string $id)
  {
	//
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy (string $id)
  {
	//
  }

  /**
   * Get all permissions.
   */

  public function all (PermissionRepository $permissionRepository): JsonResponse
  {
	$permissions = $permissionRepository->get();

	$data = [
		'permissions' => $permissions
	];

	return $this->sendSuccess($data);
  }

}
