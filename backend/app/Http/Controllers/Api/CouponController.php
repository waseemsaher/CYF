<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\Actions\CalculateCoursePrice;
use App\Domain\Payments\Actions\ValidateCoupon;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Live preview/validate a coupon code for a course purchase.
     */
    public function validateCoupon(
        Request $request,
        ValidateCoupon $validateCoupon,
        CalculateCoursePrice $calculateCoursePrice,
    ): JsonResponse {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'code' => ['required', 'string', 'max:50'],
        ]);

        /** @var User|null $user */
        $user = $request->user();

        /** @var Course $course */
        $course = Course::query()->findOrFail($validated['course_id']);

        $coupon = $validateCoupon->handle(
            code: (string) $validated['code'],
            course: $course,
            user: $user,
            lockForUpdate: false,
        );

        $pricing = $calculateCoursePrice->handle(
            course: $course,
            at: null,
            coupon: $coupon,
        );

        return response()->json([
            'valid' => true,
            'coupon' => [
                'id' => $coupon->getKey(),
                'code' => $coupon->getAttribute('code'),
                'type' => $coupon->getAttribute('type'),
                'value' => (int) $coupon->getAttribute('value'),
            ],
            'pricing' => $pricing,
        ]);
    }
}
