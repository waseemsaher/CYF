<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Enrollment\Actions\GrantEnrollment;
use App\Domain\Enrollment\Actions\RevokeEnrollment;
use App\Http\Controllers\Controller;
use App\Http\Requests\GrantEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminEnrollmentController extends Controller
{
    /**
     * Admin grants an enrollment without a payment.
     */
    public function grant(GrantEnrollmentRequest $request, GrantEnrollment $grantEnrollment): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $this->authorizeAdmin($admin);

        /** @var User $student */
        $student = User::query()->findOrFail($request->validated('user_id'));

        /** @var Course $course */
        $course = Course::query()->findOrFail($request->validated('course_id'));

        /** @var Term $term */
        $term = Term::query()->findOrFail($request->validated('term_id'));

        $enrollment = $grantEnrollment->handle($student, $course, $term, $admin);

        return (new EnrollmentResource($enrollment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Admin revokes an enrollment.
     */
    public function revoke(int $id, RevokeEnrollment $revokeEnrollment): JsonResponse
    {
        /** @var User $admin */
        $admin = request()->user();
        $this->authorizeAdmin($admin);

        /** @var Enrollment $enrollment */
        $enrollment = Enrollment::query()->findOrFail($id);

        $revokeEnrollment->handle($enrollment, $admin);

        return (new EnrollmentResource($enrollment->fresh()))->response();
    }

    /**
     * Admin extends an enrollment's expiry.
     */
    public function extend(int $id, Request $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $this->authorizeAdmin($admin);

        $request->validate([
            'expires_at' => ['required', 'date', 'after:now'],
        ]);

        /** @var Enrollment $enrollment */
        $enrollment = Enrollment::query()->findOrFail($id);

        $enrollment->update([
            'expires_at' => $request->input('expires_at'),
        ]);

        activity('enrollment')
            ->performedOn($enrollment)
            ->causedBy($admin)
            ->withProperties([
                'enrollment_id' => $enrollment->getKey(),
                'new_expires_at' => $request->input('expires_at'),
            ])
            ->log('enrollment_extended');

        return (new EnrollmentResource($enrollment->fresh()))->response();
    }

    private function authorizeAdmin(User $user): void
    {
        if (! $user->hasRole('superadmin') && ! $user->hasRole('admin')) {
            abort(403);
        }
    }
}
