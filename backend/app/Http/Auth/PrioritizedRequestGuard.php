<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Models\User;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\TransientToken;

class PrioritizedRequestGuard extends RequestGuard
{
    /**
     * Get the currently authenticated user.
     * Ensures an explicit Bearer token in the request header is always honored
     * over ambient session cookies or stale in-memory cached tokens from previous requests.
     *
     * @return Authenticatable|null
     */
    public function user()
    {
        $bearerToken = $this->request->bearerToken();

        if ($this->user instanceof User && $bearerToken) {
            $currentToken = $this->user->currentAccessToken();

            if ($currentToken instanceof TransientToken) {
                // User was loaded from session cookie (TransientToken), but request explicitly provided Bearer token
                $this->user = null;
            } elseif ($currentToken instanceof PersonalAccessToken) {
                $model = Sanctum::$personalAccessTokenModel;
                $tokenModel = $model::findToken($bearerToken);
                if (! $tokenModel || $tokenModel->id !== $currentToken->id) {
                    $this->user = null;
                }
            }
        }

        return parent::user();
    }
}
