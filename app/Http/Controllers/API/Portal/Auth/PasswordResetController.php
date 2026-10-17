<?php

namespace App\Http\Controllers\API\Portal\Auth;

use App\Http\Controllers\BaseController;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * "Forgot password" for the guest site and the admin panel.
 *
 * The answer to "send me a link" is always the same, whether or not the email is registered, so the
 * form cannot be used to find out who has an account. The link opens the reset page of whichever app
 * asked (only those two apps are allowed — the address is never taken from the request).
 */
class PasswordResetController extends BaseController
{
    private const SENT_MESSAGE = 'If that email is registered, we have sent a link to reset the password.';

    public function sendLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'app'   => ['nullable', 'in:guest,admin'],
        ]);

        $isAdminApp = ($data['app'] ?? 'guest') === 'admin';

        try {
            // The callback replaces Laravel's own notification with our branded email
            Password::broker()->sendResetLink(
                ['email' => $data['email']],
                function (User $user, string $token) use ($isAdminApp): string {
                    // Only owners and staff have a reason to reset a dashboard password
                    if ($isAdminApp && !$user->is_admin && !$user->is_merchant) {
                        return $token;
                    }

                    $base = rtrim((string) config($isAdminApp ? 'app.admin_url' : 'app.frontend_url'), '/');
                    $url  = $base . '/reset-password?' . http_build_query(['token' => $token, 'email' => $user->email]);

                    Mail::to($user->email, $user->name)->send(new PasswordResetMail(
                        $user->name ?: 'there',
                        $url,
                        (int) config('auth.passwords.users.expire', 60),
                        $isAdminApp,
                    ));

                    return $token;
                }
            );
        } catch (\Throwable $e) {
            // Even a mail failure must not tell the visitor anything about the account
            Log::warning('Password reset email was not sent: ' . $e->getMessage());
        }

        return $this->sendSuccess([], self::SENT_MESSAGE);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Anyone signed in with the old password is signed out
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->sendError('This reset link is invalid or has expired. Please ask for a new one.', [], 422);
        }

        return $this->sendSuccess([], 'Your password has been changed. You can sign in now.');
    }
}
