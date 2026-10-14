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
use App\Models\Setting;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class PaymentController extends Controller
{
    /**
     * Get active payment methods and recipient details.
     */
    public function paymentMethods(): JsonResponse
    {
        $methods = Setting::getValue('payments', 'methods');

        if (! is_array($methods)) {
            $methods = [
                [
                    'key' => 'vodafone_cash',
                    'label' => ['ar' => 'فودافون كاش', 'en' => 'Vodafone Cash'],
                    'type' => 'ewallet',
                    'number' => '01009971545',
                    'instructions' => [
                        'ar' => 'حوّل المبلغ المطلوب على رقم فودافون كاش الموضح، ثم ارفع صورة الإيصال.',
                        'en' => 'Transfer the required amount to the Vodafone Cash number shown, then upload the receipt screenshot.',
                    ],
                    'is_active' => true,
                ],
                [
                    'key' => 'instapay',
                    'label' => ['ar' => 'إنستاباي', 'en' => 'InstaPay'],
                    'type' => 'instapay',
                    'number' => 'wasimsaher@instapay',
                    'instructions' => [
                        'ar' => 'حوّل المبلغ المطلوب عبر إنستاباي إلى الحساب الموضح، ثم ارفع صورة الإيصال.',
                        'en' => 'Transfer the required amount via InstaPay to the account shown, then upload the receipt screenshot.',
                    ],
                    'is_active' => true,
                ],
                [
                    'key' => 'other_ewallet',
                    'label' => ['ar' => 'محفظة إلكترونية أخرى', 'en' => 'Other e-wallet'],
                    'type' => 'ewallet',
                    'number' => '01009971545',
                    'instructions' => [
                        'ar' => 'حوّل المبلغ المطلوب بأي محفظة إلكترونية، ثم ارفع صورة الإيصال مع رقم المحفظة المرسل منها.',
                        'en' => 'Transfer the required amount using any e-wallet, then upload the receipt screenshot with the sender wallet number.',
                    ],
                    'is_active' => true,
                ],
            ];
        }

        $activeMethods = array_values(array_filter($methods, function ($m) {
            return is_array($m) && (! isset($m['is_active']) || $m['is_active']);
        }));

        return response()->json([
            'data' => $activeMethods,
        ])->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=3600');
    }

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

        try {
            $result = $submitPayment->handle($user, $course, $term, $proof, [
                'method' => $request->validated('method'),
                'sender_identifier' => $request->validated('sender_identifier'),
                'student_note' => $request->validated('student_note'),
                'coupon_code' => $request->validated('coupon_code'),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

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

        try {
            $cancelled = $cancelPayment->handle($payment, $user);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new PaymentResource($cancelled->fresh()))->response();
    }
}
