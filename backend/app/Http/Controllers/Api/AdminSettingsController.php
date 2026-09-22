<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorizeAdmin();

        $settings = Setting::query()->get()->groupBy('group')->map(function ($groupSettings) {
            return $groupSettings->mapWithKeys(function (Setting $s) {
                return [$s->key => $s->value];
            });
        });

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.group' => ['required', 'string'],
            'settings.*.key' => ['required', 'string'],
            'settings.*.value' => ['required'],
        ]);

        foreach ($validated['settings'] as $item) {
            Setting::setValue($item['group'], $item['key'], $item['value']);
        }

        activity('settings')
            ->causedBy($request->user())
            ->withProperties(['updated_count' => count($validated['settings'])])
            ->log('settings_updated');

        return response()->json(['message' => 'Settings updated successfully.']);
    }

    private function authorizeAdmin(): void
    {
        /** @var User $user */
        $user = request()->user();
        if (! $user->hasRole(['superadmin', 'admin'])) {
            abort(403);
        }
    }
}
