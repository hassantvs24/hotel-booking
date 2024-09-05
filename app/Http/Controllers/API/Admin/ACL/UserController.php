<?php

namespace App\Http\Controllers\API\Admin\ACL;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Acl\UserRequest;
use App\Repositories\Admin\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends BaseController
{
  /**
   * Display a listing of the resource.
   */
  public function index (Request $request, UserRepository $userRepository): \Illuminate\Http\JsonResponse
  {
	$userQuery = array_merge($request->only([
		'search',
		'order_by',
		'order_type',
		'filters',
		'per_page'
	]),
		[
			'with' => ['roles'],
		]);
	$users = $userRepository->get($userQuery);

	$data = [
		'users' => $users,
	];

	return $this->sendSuccess($data);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store (UserRequest $request, UserRepository $userRepository): JsonResponse
  {
	try {
	  DB::beginTransaction();

	  $user = $userRepository->create($request->validated());
	  $user->roles()->sync($request->roles);
	  $user->load('roles');

	  DB::commit();

	  return $this->sendSuccess($user, 'User created successfully.');

	} catch (\Exception $e) {
	  DB::rollBack();
	  return $this->sendError(
		  'User creation failed.',
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
  public function update (UserRequest $request, UserRepository $userRepository, $user): JsonResponse
  {
	try {
	  DB::beginTransaction();

	  $user = $userRepository->update($request->validated(), $user);
	  $user->roles()->sync($request->roles);
	  $user->load('roles');

	  DB::commit();

	  return $this->sendSuccess($user, 'User updated successfully.');

	} catch (\Exception $e) {
	  DB::rollBack();
	  return $this->sendError(
		  'User update failed.',
		  (array)$e->getMessage()
	  );
	}
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy (UserRepository $userRepository, $user): JsonResponse
  {
	try {
	  DB::beginTransaction();
	  $user = $userRepository->find($user);

	  if (!$user) {
		return $this->sendError('User not found.');
	  }

	  $user->roles()->detach();
	  $userRepository->delete($user->id);

	  DB::commit();

	  return $this->sendSuccess([], 'User deleted successfully.');

	} catch (\Exception $e) {
	  DB::rollBack();
	  return $this->sendError(
		  'User deletion failed.',
		  (array)$e->getMessage()
	  );
	}
  }
}
