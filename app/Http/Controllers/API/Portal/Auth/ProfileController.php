<?php

namespace App\Http\Controllers\API\Portal\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Portal\Auth\ProfileRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends BaseController
{
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
}
