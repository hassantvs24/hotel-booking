<?php

namespace App\Http\Controllers\API\Portal\Auth;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends BaseController
{
    public function redirectToProvider($provider): RedirectResponse
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback($provider): RedirectResponse
    {
        $userSocial = Socialite::driver($provider)->stateless()->user();

        $user = User::query()->firstOrCreate(
            ['email' => $userSocial->getEmail()],
            [
                'name'     => $userSocial->getName(),
                'email'    => $userSocial->getEmail(),
                'password' => Hash::make('password')
            ]
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return redirect()->away(config('app.frontend_url') . '/login/?token=' . $token);
    }
}
