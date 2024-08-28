<?php

namespace App\Http\Controllers\API\Portal\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

            if (!auth()->attempt($credentials)) {
                return $this->sendError('These credentials do not match our records', ['error' => 'Unauthorized'], 401);
            }

            $user = auth()->user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->sendResponse([
                'user'  => $user,
                'token' => $token,
            ], 'User logged in successfully.');

        } catch (\Exception $e) {
            return $this->sendError('Error', ['errors' => $e->getMessage()], 500);
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
