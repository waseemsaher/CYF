<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        $query = Activity::query()
            ->with('causer')
            ->orderByDesc('id');

        if ($request->filled('log_name')) {
            $query->where('log_name', (string) $request->query('log_name'));
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', (int) $request->query('causer_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', (string) $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', (string) $request->query('date_to'));
        }

        $activities = $query->paginate($perPage);

        return response()->json([
            'data' => $activities->getCollection()->map(fn (Activity $act): array => [
                'id' => $act->id,
                'description' => $act->description,
                'log_name' => $act->log_name,
                'causer_name' => $act->causer instanceof User ? $act->causer->name : 'System',
                'created_at' => $act->created_at?->toIso8601String(),
                'subject_type' => $act->subject_type,
                'subject_id' => $act->subject_id,
            ])->all(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }
}
