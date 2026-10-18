<?php

namespace App\Http\Controllers\API\Portal\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends BaseController
{
    /**
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request) : JsonResponse
    {
        try {
            $credentials = $request->only('email', 'password');

            // Guessing passwords: 5 wrong tries for one email from one address, then a one-minute wait
            $limitKey = 'login:' . Str::lower((string) $request->input('email')) . '|' . $request->ip();

            if (RateLimiter::tooManyAttempts($limitKey, 5)) {
                return $this->sendError('Too many sign-in attempts. Please wait ' . RateLimiter::availableIn($limitKey) . ' seconds and try again.', [], 429);
            }

            if (!auth()->attempt($credentials)) {
                RateLimiter::hit($limitKey, 60);

                return $this->sendError('These credentials do not match our records', ['error' => 'Unauthorized'], 401);
            }

            RateLimiter::clear($limitKey);

            $user = auth()->user();

            if ($request->input('login_from') === 'admin') {
                if (!$user->is_admin && !$user->is_merchant) {
                    auth()->logout();
                    return $this->sendError('You are not authorized to login here', [], 401);
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->sendSuccess([
                'user'  => $user,
                'token' => $token,
            ], 'User logged in successfully.');

        } catch (\Exception $e) {
            report($e);

            return $this->sendError('Something went wrong. Please try again.', [], 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request) : JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return $this->sendSuccess([], 'User logged out successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error', ['errors' => $e->getMessage()], 500);
        }
    }

}
