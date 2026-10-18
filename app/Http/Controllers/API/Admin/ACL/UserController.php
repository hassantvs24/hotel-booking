<?php

namespace App\Http\Controllers\API\Admin\ACL;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Acl\UserRequest;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Repositories\Admin\UserRepository;
use App\Services\Booking\PaidBookingGuard;
use App\Services\Room\RoomUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, UserRepository $userRepository): \Illuminate\Http\JsonResponse
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
                'with' => ['roles'],
            ]);
        $users = $userRepository->paginate($userQuery);

        $data = [
            'users' => $users,
        ];

        return $this->sendSuccess($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request, UserRepository $userRepository): JsonResponse
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
    public function create()
    {
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, UserRepository $userRepository, $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $userRepository->getModel($user);

            $user = $userRepository->update($request->except(['password']), $user);
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
    public function destroy(UserRepository $userRepository, $user): JsonResponse
    {
        try {
            DB::beginTransaction();
            $user = $userRepository->find($user);

            if (!$user) {
                return $this->sendError('User not found.');
            }

            if ($user->id === auth()->id()) {
                DB::rollBack();

                return $this->sendError('You cannot delete your own account.', [], 422);
            }

            if ($user->is_admin && User::admins()->where('id', '!=', $user->id)->doesntExist()) {
                DB::rollBack();

                return $this->sendError('This is the only admin. Make someone else an admin before deleting this account.', [], 422);
            }

            // A guest who is about to stay, or an owner whose property is about to host a paid stay, cannot just disappear.
            $ownRooms = fn ($q) => $q->whereHas('property', fn ($p) => $p->where('user_id', $user->id));
            $upcoming = app(PaidBookingGuard::class)->upcomingWithMoney(
                Booking::query()->where(fn ($q) => $q->where('user_id', $user->id)->orWhereHas('room', $ownRooms))
            );

            if ($upcoming->isNotEmpty()) {
                DB::rollBack();

                return $this->sendError(
                    "This user still has {$upcoming->count()} paid booking(s) whose stay is not over yet (as the guest, or as the owner of the property). Refund them, or wait until the stays are over.",
                    [],
                    422
                );
            }

            // The account is hidden, not erased: bookings, payments, refunds, reviews and agreements stay for the records.
            $propertyIds = Property::where('user_id', $user->id)->pluck('id');
            if ($propertyIds->isNotEmpty()) {
                Property::whereIn('id', $propertyIds)->where('status', 'Published')->update(['status' => 'Unpublished']);
                app(RoomUsage::class)->release(Room::whereIn('property_id', $propertyIds)->pluck('id')->all());
            }

            $user->tokens()->delete();            // nobody can stay signed in as this account
            $user->roles()->detach();
            $user->email = substr('deleted-' . $user->id . '-' . $user->email, 0, 190);   // frees the address for a new sign-up
            $user->save();
            $userRepository->delete($user->id);   // soft delete

            DB::commit();

            return $this->sendSuccess([], 'User deleted. Their bookings, payments and reviews are kept for the records.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(
                'User deletion failed.',
                (array)$e->getMessage()
            );
        }
    }
}
