<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Identity\Actions\GetCurrentUserProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(private readonly GetCurrentUserProfileAction $profileAction) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }
        Auth::forgetGuards();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

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

        Auth::guard('web')->login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $payload = $this->profileAction->handle($user);

        return response()->json([
            'data' => [
                'user' => $payload['user'],
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

        if (! $user->is_active) {
            $user->tokens()->delete();
            Auth::guard('web')->logout();
            Auth::forgetGuards();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json([
                'message' => 'This account has been deactivated.',
            ], 403);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $payload = $this->profileAction->handle($user);

        return response()->json([
            'data' => [
                'user' => $payload['user'],
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user instanceof User) {
            $currentToken = $user->currentAccessToken();
            if ($currentToken instanceof PersonalAccessToken) {
                $currentToken->delete();
            }
        }

        Auth::guard('web')->logout();
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'message' => 'لو كان البريد الإلكتروني ده مسجل عندنا، هيوصلك رابط إعادة تعيين كلمة السر خلال دقائق.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ]);
        }

        return response()->json([
            'message' => __($status),
        ], 400);
    }
}
