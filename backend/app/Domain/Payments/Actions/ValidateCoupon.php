<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Coupon;
use App\Models\Course;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class ValidateCoupon
{
    /**
     * Validate coupon code against course, user, and redemption limits.
     * Throws ValidationException with translated Arabic messages on failure.
     */
    public function handle(string $code, Course $course, ?User $user = null, bool $lockForUpdate = false, ?CarbonImmutable $at = null): Coupon
    {
        $normalizedCode = strtoupper(trim($code));

        if ($normalizedCode === '') {
            throw ValidationException::withMessages([
                'coupon_code' => [__('كوبون الخصم غير صالح أو غير موجود.')],
            ]);
        }

        $query = Coupon::query()->where('code', $normalizedCode);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        /** @var Coupon|null $coupon */
        $coupon = $query->first();

        if ($coupon === null) {
            throw ValidationException::withMessages([
                'coupon_code' => [__('كوبون الخصم غير صالح أو غير موجود.')],
            ]);
        }

        if (! $coupon->getAttribute('is_active')) {
            throw ValidationException::withMessages([
                'coupon_code' => [__('هذا الكوبون غير مفعّل حاليًا.')],
            ]);
        }

        $now = $at ?? CarbonImmutable::now();

        /** @var CarbonInterface|null $startsAt */
        $startsAt = $coupon->getAttribute('starts_at');
        /** @var CarbonInterface|null $endsAt */
        $endsAt = $coupon->getAttribute('ends_at');

        if (($startsAt && $now->lt($startsAt)) || ($endsAt && $now->gt($endsAt))) {
            throw ValidationException::withMessages([
                'coupon_code' => [__('هذا الكوبون منتهي الصلاحية أو لم يبدأ بعد.')],
            ]);
        }

        if ($coupon->getAttribute('scope') === 'courses') {
            $courseMatch = $coupon->courses()->whereKey($course->getKey())->exists();

            if (! $courseMatch) {
                throw ValidationException::withMessages([
                    'coupon_code' => [__('هذا الكوبون غير صالح لهذه المادة.')],
                ]);
            }
        }

        $maxRedemptions = $coupon->getAttribute('max_redemptions');
        if ($maxRedemptions !== null) {
            $redemptionsCount = $coupon->redemptions()
                ->where(function ($q): void {
                    $q->whereNull('payment_id')
                        ->orWhereDoesntHave('payment', function ($pq): void {
                            $pq->whereIn('status', ['rejected', 'cancelled']);
                        });
                })
                ->count();

            if ($redemptionsCount >= (int) $maxRedemptions) {
                throw ValidationException::withMessages([
                    'coupon_code' => [__('تم الوصول للحد الأقصى لاستخدام هذا الكوبون.')],
                ]);
            }
        }

        if ($user !== null) {
            $maxPerUser = (int) $coupon->getAttribute('max_redemptions_per_user');
            $userRedemptionsCount = $coupon->redemptions()
                ->where('user_id', $user->getKey())
                ->where(function ($q): void {
                    $q->whereNull('payment_id')
                        ->orWhereDoesntHave('payment', function ($pq): void {
                            $pq->whereIn('status', ['rejected', 'cancelled']);
                        });
                })
                ->count();

            if ($userRedemptionsCount >= $maxPerUser) {
                throw ValidationException::withMessages([
                    'coupon_code' => [__('لقد استنفدت الحد الأقصى المسموح به لاستخدام هذا الكوبون.')],
                ]);
            }
        }

        return $coupon;
    }
}
