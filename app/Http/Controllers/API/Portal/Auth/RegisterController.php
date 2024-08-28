<?php

namespace App\Http\Controllers\API\Portal\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegisterController extends BaseController
{
    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request) : JsonResponse
    {
        try {

            $user = User::query()->create([
                'name'     => $request->input('name'),
                'email'    => $request->input('email'),
                'password' => bcrypt($request->input('password')),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->sendSuccess([
                'user'  => $user,
                'token' => $token,
            ], 'User registered successfully.');

        } catch (\Exception $e) {
            return $this->sendError('Error', ['errors' => $e->getMessage()], 500);
        }
    }
}
