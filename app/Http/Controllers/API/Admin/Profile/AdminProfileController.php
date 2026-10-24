<?php

namespace App\Http\Controllers\API\Admin\Profile;

use App\Http\Controllers\BaseController;
use App\Models\Property;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * "My profile" in the admin panel, for the platform admin and for hotel owners. Everybody sees and changes ONLY their own
 * details: the person is always the signed-in one, never taken from the request (so nobody can reach another person's
 * profile or password). The email is what the person signs in with and is shown, not changed. A photo is changed through
 * the existing /auth/profile/update/photo routes, which work for any signed-in person.
 */
class AdminProfileController extends BaseController
{
    public function show(Request $request): JsonResponse
    {
        return $this->sendSuccess($this->payload($request->user()));
    }

    /** Name and phone only. Anything else in the request (email, roles, password...) is ignored on purpose. */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{6,20}$/'],
        ], ['phone.regex' => 'Please enter a valid phone number (digits, +, - and spaces).']);

        $name = trim((string) preg_replace('/\s+/u', ' ', $data['name']));

        if ($problem = self::nameProblem($name)) {
            return $this->sendError($problem, ['name' => [$problem]], 422);
        }

        $was = $user->name;
        $user->forceFill(['name' => $name, 'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null])->save();

        // an owner's change is only news for the admin; the admin's own change needs nobody's attention
        if (!$user->is_admin && $was !== $name) {
            User::admins()->get()->each(fn (User $admin) => $admin->notify(new AdminNotification(
                'property_owner_renamed', "{$was} changed their name", "Their name is now {$name}.", 'bx-user', 'teal',
                ['user_id' => $user->id, 'always' => true],
            )));
        }

        return $this->sendSuccess($this->payload($user->fresh()), 'Saved.');
    }

    /**
     * The person's OWN password: they must know the current one, and the new one must be different. Every other
     * place they are signed in is signed out (the one they are using stays). Another person's password is never touched.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'max:100', 'confirmed', 'different:current_password'],
        ], [
            'password.min'       => 'The new password needs at least 8 characters.',
            'password.confirmed' => 'The two new passwords do not match.',
            'password.different' => 'The new password must be different from the current one.',
        ]);

        if (!Hash::check($data['current_password'], (string) $user->getAuthPassword())) {
            return $this->sendError('The current password is not correct.', ['current_password' => ['The current password is not correct.']], 422);
        }

        $user->forceFill(['password' => $data['password']])->save();

        $current = $user->currentAccessToken();
        $keep = $current instanceof PersonalAccessToken ? $current->id : null;
        $user->tokens()->when($keep, fn ($q) => $q->where('id', '!=', $keep))->delete();

        return $this->sendSuccess(null, 'Your password was changed. You were signed out everywhere else.');
    }

    /** Why a person's name is not acceptable, or null */
    public static function nameProblem(string $name): ?string
    {
        if (mb_strlen($name) < 2) {
            return 'The name needs at least 2 letters.';
        }

        if (mb_strlen($name) > 100) {
            return 'The name is too long (100 letters at most).';
        }

        if (preg_match('/\d(?:[\s().\-]*\d){5,}/u', $name)) {
            return 'The name cannot contain a phone number.';
        }

        if (preg_match('/[<>]/', $name)) {
            return 'The name cannot contain < or >.';
        }

        return null;
    }

    private function payload(User $user): array
    {
        $hotels = $user->is_merchant && !$user->is_admin
            ? Property::where('user_id', $user->id)->orderBy('id')->get(['id', 'name', 'status', 'verified_at'])
                ->map(fn (Property $p) => ['id' => $p->id, 'name' => $p->name, 'status' => $p->status, 'verified' => !empty($p->verified_at), 'verified_at' => $p->verified_at])
                ->values()
            : [];

        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'phone'             => $user->phone,
            'role'              => $user->is_admin ? 'Admin' : ($user->is_merchant ? 'Hotel owner' : 'Staff'),
            'is_admin'          => (bool) $user->is_admin,
            'is_merchant'       => (bool) $user->is_merchant,
            'profile_photo_url' => $user->profile_photo_url,
            'member_since'      => $user->created_at?->toDateString(),
            'hotels'            => $hotels,
        ];
    }
}
