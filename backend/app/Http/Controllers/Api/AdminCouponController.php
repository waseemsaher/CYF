<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminCouponController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('manage', Coupon::class);

        $coupons = Coupon::query()
            ->with(['courses:id,slug,title'])
            ->withCount([
                'redemptions as active_redemptions_count' => function (Builder $query): void {
                    $query->where(function (Builder $subQuery): void {
                        $subQuery->whereNull('payment_id')
                            ->orWhereDoesntHave('payment', function (Builder $pq): void {
                                $pq->whereIn('status', ['rejected', 'cancelled']);
                            });
                    });
                },
            ])
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json([
            'data' => $coupons->getCollection()->map(fn (Coupon $coupon): array => $this->payload($coupon))->all(),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        Gate::authorize('manage', Coupon::class);

        /** @var User $admin */
        $admin = $request->user();

        $data = $request->validated();
        $courseIds = $data['course_ids'] ?? [];
        unset($data['course_ids']);

        /** @var Coupon $coupon */
        $coupon = Coupon::query()->create($data);

        if ($coupon->getAttribute('scope') === 'courses') {
            $coupon->courses()->sync($courseIds);
        }

        activity('coupon')
            ->performedOn($coupon)
            ->causedBy($admin)
            ->withProperties($request->validated())
            ->log("Admin {$admin->name} created coupon {$coupon->code}.");

        $coupon->load(['courses:id,slug,title']);
        $coupon->setAttribute('active_redemptions_count', 0);

        return response()->json(['data' => $this->payload($coupon)], 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        Gate::authorize('manage', Coupon::class);

        $coupon->load(['courses:id,slug,title']);

        /** @var Collection<int, CouponRedemption> $redemptions */
        $redemptions = $coupon->redemptions()
            ->with([
                'user:id,name,email,branch',
                'payment:id,amount_due_cents,status,created_at',
            ])
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $activeCount = $coupon->activeRedemptionsCount();
        $coupon->setAttribute('active_redemptions_count', $activeCount);

        $data = $this->payload($coupon);
        $data['redemptions'] = $redemptions->map(fn (CouponRedemption $r): array => [
            'id' => $r->getKey(),
            'user' => $r->user ? [
                'id' => $r->user->getKey(),
                'name' => $r->user->getAttribute('name'),
                'email' => $r->user->getAttribute('email'),
                'branch' => $r->user->getAttribute('branch'),
            ] : null,
            'payment' => $r->payment ? [
                'id' => $r->payment->getKey(),
                'amount_due_cents' => (int) $r->payment->getAttribute('amount_due_cents'),
                'status' => $r->payment->getAttribute('status'),
                'created_at' => $r->payment->getAttribute('created_at')?->toISOString(),
            ] : null,
            'created_at' => $r->getAttribute('created_at')?->toISOString(),
        ])->all();

        return response()->json(['data' => $data]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        Gate::authorize('manage', Coupon::class);

        /** @var User $admin */
        $admin = $request->user();

        $data = $request->validated();
        $courseIds = $data['course_ids'] ?? [];
        unset($data['course_ids']);

        $coupon->update($data);

        if ($coupon->getAttribute('scope') === 'courses') {
            $coupon->courses()->sync($courseIds);
        } else {
            $coupon->courses()->detach();
        }

        activity('coupon')
            ->performedOn($coupon)
            ->causedBy($admin)
            ->withProperties($request->validated())
            ->log("Admin {$admin->name} updated coupon {$coupon->code}.");

        $coupon->load(['courses:id,slug,title']);
        $coupon->setAttribute('active_redemptions_count', $coupon->activeRedemptionsCount());

        return response()->json(['data' => $this->payload($coupon)]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        Gate::authorize('manage', Coupon::class);

        /** @var User $admin */
        $admin = request()->user();

        $code = $coupon->getAttribute('code');
        $id = $coupon->getKey();

        $coupon->delete();

        activity('coupon')
            ->performedOn($coupon)
            ->causedBy($admin)
            ->withProperties(['coupon_id' => $id, 'code' => $code])
            ->log("Admin {$admin->name} deleted coupon {$code}.");

        return response()->json(null, 204);
    }

    public function toggleActive(Coupon $coupon): JsonResponse
    {
        Gate::authorize('manage', Coupon::class);

        /** @var User $admin */
        $admin = request()->user();

        $newActive = ! (bool) $coupon->getAttribute('is_active');
        $coupon->update(['is_active' => $newActive]);

        activity('coupon')
            ->performedOn($coupon)
            ->causedBy($admin)
            ->withProperties(['is_active' => $newActive])
            ->log("Admin {$admin->name} toggled active status for coupon {$coupon->code} to ".($newActive ? 'active' : 'inactive').'.');

        $coupon->load(['courses:id,slug,title']);
        $coupon->setAttribute('active_redemptions_count', $coupon->activeRedemptionsCount());

        return response()->json(['data' => $this->payload($coupon)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Coupon $coupon): array
    {
        return [
            'id' => $coupon->getKey(),
            'code' => $coupon->getAttribute('code'),
            'type' => $coupon->getAttribute('type'),
            'value' => (int) $coupon->getAttribute('value'),
            'scope' => $coupon->getAttribute('scope'),
            'max_redemptions' => $coupon->getAttribute('max_redemptions'),
            'max_redemptions_per_user' => (int) $coupon->getAttribute('max_redemptions_per_user'),
            'starts_at' => $coupon->getAttribute('starts_at')?->toISOString(),
            'ends_at' => $coupon->getAttribute('ends_at')?->toISOString(),
            'is_active' => (bool) $coupon->getAttribute('is_active'),
            'times_redeemed' => (int) ($coupon->getAttribute('active_redemptions_count') ?? $coupon->activeRedemptionsCount()),
            'courses' => $coupon->relationLoaded('courses')
                ? $coupon->courses->map(fn ($c): array => [
                    'id' => $c->getKey(),
                    'slug' => $c->getAttribute('slug'),
                    'title' => $c->getTranslations('title'),
                ])->all()
                : [],
            'created_at' => $coupon->getAttribute('created_at')?->toISOString(),
            'updated_at' => $coupon->getAttribute('updated_at')?->toISOString(),
        ];
    }
}
