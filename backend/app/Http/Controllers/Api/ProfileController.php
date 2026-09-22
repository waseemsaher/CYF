<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Identity\Actions\GetCurrentUserProfileAction;
use App\Domain\Identity\Actions\UpdateCurrentUserProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        private readonly GetCurrentUserProfileAction $getProfileAction,
        private readonly UpdateCurrentUserProfileAction $updateProfileAction,
    ) {}

    public function show(): JsonResponse
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $payload = $this->getProfileAction->handle($user);

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = $this->updateProfileAction->handle($user, $request->validated());
        $payload = $this->getProfileAction->handle($user);

        return response()->json([
            'data' => $payload,
        ]);
    }
}
