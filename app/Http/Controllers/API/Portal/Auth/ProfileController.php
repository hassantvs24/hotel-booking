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

    /**
     * Saves the parts of the profile that were sent, and only those (the page changes one row at a time).
     * The email is not changed here: it is what the guest signs in with and where confirmations go.
     */
    public function updatePersonalInfo(ProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $v = $request->validated();

            $profileData = array_intersect_key($v, array_flip([
                'first_name', 'last_name', 'date_of_birth', 'gender', 'address', 'city', 'state', 'zip_code', 'country', 'nid', 'passport',
            ]));

            $userData = [];

            if (array_key_exists('phone', $v)) {
                $userData['phone'] = $v['phone'];
            }

            if (array_key_exists('first_name', $v) || array_key_exists('last_name', $v)) {
                $known = $user->profile;
                $first = $v['first_name'] ?? ($known?->first_name ?: explode(' ', trim((string) $user->name))[0]);
                $last  = array_key_exists('last_name', $v) ? $v['last_name'] : $known?->last_name;
                $name  = trim($first . ' ' . $last);

                if ($name !== '') {
                    $userData['name'] = $name;
                }
            }

            if ($userData) {
                $user->update($userData);
            }

            if ($profileData) {
                $user->profile()->updateOrCreate(['user_id' => $user->id], $profileData);
            }

            return $this->sendSuccess($user->fresh()->load('profile'), 'Profile updated successfully.');
        } catch (\Exception $e) {
            report($e);   // the cause goes to the log, not to the guest

            return $this->sendError('Something went wrong. Please try again.', []);
        }
    }

    /** A new profile photo: a picture of any common kind, scaled down, kept on the public disk. The old one is removed. */
    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate(['photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192']], [
            'photo.image' => 'Please choose a picture (JPG, PNG or WebP).',
            'photo.mimes' => 'Please choose a picture (JPG, PNG or WebP).',
            'photo.max' => 'The picture is too big. Please choose one under 8 MB.',
        ]);

        $user = $request->user();
        $file = $request->file('photo');
        $optimized = app(\App\Services\Media\ImageOptimizer::class)->optimize($file);
        $extension = $optimized['extension'] ?? (strtolower($file->extension() ?: 'jpg'));
        $path = 'profile-photos/' . $user->id . '/' . now()->format('YmdHis') . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)) . '.' . $extension;

        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $optimized['contents'] ?? file_get_contents($file->getRealPath()));
        $this->forgetPhoto($user);
        $user->update(['profile_photo' => $path]);

        return $this->sendSuccess($user->fresh()->load('profile'), 'Photo updated.');
    }

    public function removePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->forgetPhoto($user);
        $user->update(['profile_photo' => null]);

        return $this->sendSuccess($user->fresh()->load('profile'), 'Photo removed.');
    }

    /** Deletes the file of the current photo, if it is one this page saved */
    private function forgetPhoto($user): void
    {
        $old = $user->getRawOriginal('profile_photo');

        if ($old && str_starts_with($old, 'profile-photos/')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
        }
    }

    public function updatePreferenceInfo(Request $request): JsonResponse
    {
        $request->validate(['bio' => 'nullable|string|max:300'], ['bio.max' => 'Please keep it under 300 characters.']);

        try {
            $user = $request->user();

            $userProfileData = $request->only(['bio']);

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $userProfileData
            );

            return $this->sendSuccess($user->load('profile'), 'Profile updated successfully.');
        } catch (\Exception $e) {
            report($e);   // the cause goes to the log, not to the guest

            return $this->sendError('Something went wrong. Please try again.', []);
        }
    }
}
