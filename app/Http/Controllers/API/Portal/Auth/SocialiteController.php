<?php

namespace App\Http\Controllers\API\Portal\Auth;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Continue with Google / Facebook" — for the guest site only.
 * Every failure sends the guest back to the login page with a message, never to an error page.
 */
class SocialiteController extends BaseController
{
    /** Providers the guest site offers. One is usable only once its keys are in .env. */
    private const PROVIDERS = ['google', 'facebook'];

    public function redirectToProvider($provider): RedirectResponse
    {
        if (!$this->enabled($provider)) {
            return $this->backToLogin(ucfirst($provider) . ' sign-in is not available right now.');
        }

        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback(Request $request, $provider): RedirectResponse
    {
        if (!$this->enabled($provider)) {
            return $this->backToLogin(ucfirst($provider) . ' sign-in is not available right now.');
        }

        // The guest pressed Cancel, or the provider refused
        if ($request->filled('error')) {
            return $this->backToLogin('Sign-in was cancelled.');
        }

        try {
            $social = Socialite::driver($provider)->stateless()->user();
        } catch (\Throwable $e) {
            Log::warning("Social login ({$provider}) failed: " . $e->getMessage());

            return $this->backToLogin('We could not sign you in with ' . ucfirst($provider) . '. Please try again.');
        }

        // Without an email there is no account to match — never fall back to "no email" matching
        $email = Str::lower(trim((string) $social->getEmail()));
        if ($email === '') {
            return $this->backToLogin(
                ucfirst($provider) . ' did not share an email address, so we could not sign you in. Please sign up with your email instead.'
            );
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            // Admin accounts sign in with their password only, never through a social provider
            if ($user->is_admin) {
                return $this->backToLogin('This account must sign in with its email and password.');
            }
        } else {
            // Google says whether it has verified the address; Facebook does not, so it is left unverified
            $raw      = is_array($social->user ?? null) ? $social->user : [];
            $verified = (bool) ($raw['email_verified'] ?? $raw['verified_email'] ?? false);

            $user = new User([
                'name'     => $social->getName() ?: Str::before($email, '@'),
                'email'    => $email,
                // Nobody knows this: the account is entered through the provider, or via "forgot password"
                'password' => Hash::make(Str::random(40)),
            ]);
            // not in User::$fillable, so it has to be set explicitly
            if ($verified) {
                $user->forceFill(['email_verified_at' => now()]);
            }
            $user->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return redirect()->away(config('app.frontend_url') . '/login/?token=' . $token);
    }

    /** A provider can be used only if it is offered here and its keys are set in .env */
    private function enabled(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true)
            && filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }

    private function backToLogin(string $message): RedirectResponse
    {
        return redirect()->away(rtrim(config('app.frontend_url'), '/') . '/login?social_error=' . urlencode($message));
    }
}
