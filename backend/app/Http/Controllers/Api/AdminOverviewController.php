<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AdminOverviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasRole(['superadmin', 'admin']) && ! $user->can('payments.review')) {
            abort(403);
        }

        $currentTerm = Term::query()->where('is_current', true)->first();

        $pendingPaymentsCount = Payment::query()->where('status', 'pending')->count();

        $activeEnrollmentsCount = Enrollment::query()
            ->where('status', 'active')
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();

        $termRevenueQuery = Payment::query()->where('status', 'approved');
        if ($currentTerm) {
            $termRevenueQuery->where('term_id', $currentTerm->id);
        }
        $termRevenueCents = (int) $termRevenueQuery->sum('amount_due_cents');

        $totalStudentsCount = User::role('student')->count();

        $recentActivity = Activity::query()
            ->with('causer')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (Activity $act): array => [
                'id' => $act->id,
                'description' => $act->description,
                'log_name' => $act->log_name,
                'causer_name' => $act->causer instanceof User ? $act->causer->name : 'System',
                'created_at' => $act->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => [
                'pending_payments_count' => $pendingPaymentsCount,
                'active_enrollments_count' => $activeEnrollmentsCount,
                'term_revenue_cents' => $termRevenueCents,
                'total_students_count' => $totalStudentsCount,
                'recent_activity' => $recentActivity,
            ],
        ]);
    }
}
