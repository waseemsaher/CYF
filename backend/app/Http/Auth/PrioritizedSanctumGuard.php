<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\Events\TokenAuthenticated;
use Laravel\Sanctum\Guard as SanctumGuard;
use Laravel\Sanctum\Sanctum;

class PrioritizedSanctumGuard extends SanctumGuard
{
    /**
     * Retrieve the authenticated user for the incoming request.
     * Prioritizes explicit Bearer tokens over ambient session cookies to prevent
     * session hijacking or cross-user confusion when stale cookies exist.
     *
     * @return mixed
     */
    public function __invoke(Request $request)
    {
        // 1. If an explicit Bearer token is provided, prioritize it
        if ($token = $this->getTokenFromRequest($request)) {
            $model = Sanctum::$personalAccessTokenModel;

            $accessToken = $model::findToken($token);

            if (! $this->isValidAccessToken($accessToken) ||
                ! $this->supportsTokens($accessToken->tokenable)) {
                return null;
            }

            $tokenable = $accessToken->tokenable;
            if ($tokenable instanceof User) {
                $tokenable = $tokenable->withAccessToken($accessToken);
            }

            event(new TokenAuthenticated($accessToken));

            if ($this->trackLastUsedAt) {
                $this->updateLastUsedAt($accessToken);
            }

            return $tokenable;
        }

        // 2. If no Bearer token is provided, fallback to stateful session guards (for SPA cookie auth)
        return parent::__invoke($request);
    }
}
