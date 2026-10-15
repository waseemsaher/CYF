<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Payment $payment */
        $payment = $this->resource;

        return [
            'id' => $payment->getKey(),
            'user_id' => $payment->getAttribute('user_id'),
            'course_id' => $payment->getAttribute('course_id'),
            'term_id' => $payment->getAttribute('term_id'),
            'coupon_id' => $payment->getAttribute('coupon_id'),
            'coupon_code' => $payment->relationLoaded('coupon') ? $payment->coupon?->getAttribute('code') : ($payment->coupon_id ? $payment->coupon?->getAttribute('code') : null),
            'method' => $payment->getAttribute('method'),
            'list_price_cents' => (int) $payment->getAttribute('list_price_cents'),
            'discount_cents' => (int) $payment->getAttribute('discount_cents'),
            'amount_due_cents' => (int) $payment->getAttribute('amount_due_cents'),
            'sender_identifier' => $payment->getAttribute('sender_identifier'),
            'student_note' => $payment->getAttribute('student_note'),
            'status' => $payment->getAttribute('status'),
            'rejection_reason' => $payment->getAttribute('rejection_reason'),
            'teacher_share_percent' => $payment->getAttribute('teacher_share_percent'),
            'teacher_share_cents' => $payment->getAttribute('teacher_share_cents'),
            'platform_share_cents' => $payment->getAttribute('platform_share_cents'),
            'reviewed_at' => $payment->getAttribute('reviewed_at')?->toISOString(),
            'has_duplicate_proof' => $payment->hasDuplicateProof(),
            'proof_url' => $this->resolveProofUrl($payment),
            'course' => $this->whenLoaded('course', function () use ($payment): array {
                $course = $payment->course;

                return [
                    'id' => $course->getKey(),
                    'slug' => $course->getAttribute('slug'),
                    'title' => $course->getTranslations('title'),
                ];
            }),
            'user' => $this->whenLoaded('user', function () use ($payment): array {
                $user = $payment->user;

                return [
                    'id' => $user->getKey(),
                    'name' => $user->getAttribute('name'),
                    'email' => $user->getAttribute('email'),
                    'branch' => $user->getAttribute('branch'),
                    'academic_year' => $user->getAttribute('academic_year'),
                    'department' => $user->getAttribute('department'),
                ];
            }),
            'coupon' => $this->whenLoaded('coupon', function () use ($payment): ?array {
                $coupon = $payment->coupon;
                if (! $coupon) {
                    return null;
                }

                return [
                    'id' => $coupon->getKey(),
                    'code' => $coupon->getAttribute('code'),
                    'type' => $coupon->getAttribute('type'),
                    'value' => (int) $coupon->getAttribute('value'),
                ];
            }),
            'created_at' => $payment->getAttribute('created_at')?->toISOString(),
        ];
    }

    private function resolveProofUrl(Payment $payment): ?string
    {
        $proofPath = $payment->getAttribute('proof_path');
        if (! is_string($proofPath) || $proofPath === '') {
            return null;
        }

        try {
            $disk = config('filesystems.default', 'local');

            return Storage::disk($disk)->temporaryUrl(
                $proofPath,
                now()->addMinutes(60),
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
