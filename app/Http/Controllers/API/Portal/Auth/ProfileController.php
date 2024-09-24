<?php

namespace App\Http\Controllers\API\Portal\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Portal\Auth\ProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends BaseController
{
    public function me(Request $request) : JsonResponse
    {
        $user = $request->user()->load(['profile']);
        $permissions = $request->user()->getAllPermissions()->pluck('slug')->toArray();
        $roles = $request->user()->getRoleNames()->toArray();

        $data = [
            'user' => $user,
            'permissions' => $permissions,
            'roles' => $roles,
            'is_admin' => $request->user()->is_admin,
            'is_merchant' => $request->user()->is_merchant,
        ];

        return $this->sendSuccess($data, 'User profile fetched successfully.');
    }

    public function updatePersonalInfo(ProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $userData = array_merge($request->only(['email', 'phone']), [
                'name' => $request->input('first_name') . ' ' . $request->input('last_name'),
            ]);

            $userProfileData = $request->except(['name', 'email', 'phone']);

            $user->update($userData);

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $userProfileData
            );

            return $this->sendSuccess($user->load('profile'), 'Profile updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong.', [$e->getMessage()]);
        }
    }

    public function updatePreferenceInfo(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $userProfileData = $request->only(['bio']);

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $userProfileData
            );

            return $this->sendSuccess($user->load('profile'), 'Profile updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong.', [$e->getMessage()]);
        }
    }
}
