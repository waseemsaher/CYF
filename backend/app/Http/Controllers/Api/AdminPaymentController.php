<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Payments\Actions\ApprovePayment;
use App\Domain\Payments\Actions\RejectPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectPaymentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AdminPaymentController extends Controller
{
    /**
     * Admin views the payment review queue.
     */
    public function index(): JsonResponse
    {
        $this->authorize('review', Payment::class);

        $status = request()->query('status', 'pending');
        if (! is_string($status) || ! in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $status = 'pending';
        }

        $payments = Payment::query()
            ->with(['user', 'course'])
            ->where('status', $status)
            ->orderBy('created_at')
            ->paginate(20);

        // Status counts for dashboard
        $counts = Payment::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        return PaymentResource::collection($payments)
            ->additional(['counts' => $counts])
            ->response();
    }

    /**
     * Admin views a single payment with full details.
     */
    public function show(int $id): JsonResponse
    {
        $this->authorize('review', Payment::class);

        /** @var Payment $payment */
        $payment = Payment::query()
            ->with(['user', 'course', 'term'])
            ->findOrFail($id);

        $data = (new PaymentResource($payment))->resolve();

        // Generate signed URL for proof image
        $proofPath = $payment->getAttribute('proof_path');
        if (is_string($proofPath) && $proofPath !== '') {
            $data['proof_url'] = Storage::disk('local')->temporaryUrl(
                $proofPath,
                now()->addMinutes(15),
            );
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Admin approves a payment.
     */
    public function approve(int $id, ApprovePayment $approvePayment): JsonResponse
    {
        $this->authorize('review', Payment::class);

        /** @var User $user */
        $user = request()->user();

        /** @var Payment $payment */
        $payment = Payment::query()->findOrFail($id);

        $enrollment = $approvePayment->handle($payment, $user);

        return (new EnrollmentResource($enrollment))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Admin rejects a payment.
     */
    public function reject(int $id, RejectPaymentRequest $request, RejectPayment $rejectPayment): JsonResponse
    {
        $this->authorize('review', Payment::class);

        /** @var User $user */
        $user = $request->user();

        /** @var Payment $payment */
        $payment = Payment::query()->findOrFail($id);

        $rejectPayment->handle($payment, $user, $request->validated('rejection_reason'));

        return (new PaymentResource($payment->fresh()))->response();
    }
}
