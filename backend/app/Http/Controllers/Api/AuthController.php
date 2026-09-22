<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = new User;
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'branch' => $validated['branch'],
            'academic_year' => $validated['academic_year'],
            'department' => $validated['department'],
            'telegram_username' => $validated['telegram_username'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'locale' => $validated['locale'] ?? 'ar',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->save();

        $user->assignRole('student');
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->getAttribute('id'),
                    'name' => $user->getAttribute('name'),
                    'email' => $user->getAttribute('email'),
                    'role' => $user->getRoleNames()->first(),
                    'locale' => $user->getAttribute('locale'),
                ],
                'token' => $token,
            ],
        ], 201);
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->getAttribute('id'),
                    'name' => $user->getAttribute('name'),
                    'email' => $user->getAttribute('email'),
                    'role' => $user->getRoleNames()->first(),
                    'locale' => $user->getAttribute('locale'),
                ],
                'token' => $token,
            ],
        ]);
    }
}
