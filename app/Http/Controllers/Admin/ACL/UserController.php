<?php

namespace App\Http\Controllers\Admin\ACL;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\ACL\UserRequest;
use App\Models\User;
use App\Repositories\User\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, UserRepository $userRepository) : View
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
        $users = $userRepository->paginate($userQuery);
        return view('admin.acl.user.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() :View
    {
        $roles = Role::query()->pluck('name', 'id')->toArray();
        return view('admin.acl.user.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request, UserRepository $userRepository) :RedirectResponse
    {
        try {
            $user = $userRepository->create($request->only(['name', 'email', 'phone', 'password']));
            $user->roles()->attach($request->roles);

            return redirect()->route('admin.acl.users.index')->with([
                'message' => 'User created successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => 'Something Went Wrong',
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
    public function edit(User $user) :View
    {
        $roles = Role::query()->pluck('name', 'id')->toArray();
        return view('admin.acl.user.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, UserRepository $userRepository, $user) : RedirectResponse
    {
        try {

            $user = $userRepository->getModel($user);
            $userRepository->update(
                $request->only(['name', 'email', 'phone', 'password']),
                $user
            );

            $user->roles()->sync($request->roles);

            return redirect()->route('admin.acl.users.index')->with([
                    'message' => 'User updated successfully.',
                    'alert-type' => 'success'
                ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => 'Something Went Wrong',
                'alert-type' => 'error'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserRepository $userRepository, $user) : RedirectResponse
    {
        try {

            $user = $userRepository->getModel($user);
            $user->roles()->detach();

            $userRepository->delete($user->id);
            return redirect()->route('admin.acl.users.index')->with([
                    'message' => 'User deleted successfully.',
                    'alert-type' => 'success'
                ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => 'Something Went Wrong',
                'alert-type' => 'error'
            ]);
        }
    }
}
