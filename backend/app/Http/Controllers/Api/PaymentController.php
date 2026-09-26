<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Payments\Actions\CancelPayment;
use App\Domain\Payments\Actions\SubmitPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitPaymentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Http\Resources\PaymentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class PaymentController extends Controller
{
    /**
     * Student submits a payment proof.
     */
    public function store(SubmitPaymentRequest $request, SubmitPayment $submitPayment): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Course $course */
        $course = Course::query()->findOrFail($request->validated('course_id'));

        /** @var Term $term */
        $term = Term::query()->findOrFail($request->validated('term_id'));

        /** @var UploadedFile $proof */
        $proof = $request->file('proof');

        $result = $submitPayment->handle($user, $course, $term, $proof, [
            'method' => $request->validated('method'),
            'sender_identifier' => $request->validated('sender_identifier'),
            'student_note' => $request->validated('student_note'),
        ]);

        if ($result instanceof Enrollment) {
            return (new EnrollmentResource($result))
                ->response()
                ->setStatusCode(201);
        }

        return (new PaymentResource($result))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Student lists their own payments.
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        $payments = Payment::query()
            ->where('user_id', $user->getKey())
            ->with('course')
            ->orderByDesc('created_at')
            ->paginate(15);

        return PaymentResource::collection($payments)->response();
    }

    /**
     * Student views a specific payment.
     */
    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        /** @var Payment $payment */
        $payment = Payment::query()
            ->with('course')
            ->findOrFail($id);

        $this->authorize('view', $payment);

        return (new PaymentResource($payment))->response();
    }

    /**
     * Student cancels their own pending payment.
     */
    public function cancel(int $id, CancelPayment $cancelPayment): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        /** @var Payment $payment */
        $payment = Payment::query()->findOrFail($id);

        $this->authorize('cancel', $payment);

        $cancelPayment->handle($payment, $user);

        return (new PaymentResource($payment->fresh()))->response();
    }
}
