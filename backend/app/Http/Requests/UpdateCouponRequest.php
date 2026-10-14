<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->input('code'))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Coupon|string|int $coupon */
        $coupon = $this->route('coupon');
        $couponId = $coupon instanceof Coupon ? $coupon->getKey() : $coupon;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->ignore($couponId),
            ],
            'type' => ['required', 'string', Rule::in(['percent', 'fixed'])],
            'value' => [
                'required',
                'integer',
                'min:1',
                Rule::when($this->input('type') === 'percent', ['max:100']),
            ],
            'scope' => ['required', 'string', Rule::in(['all', 'courses'])],
            'course_ids' => ['required_if:scope,courses', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'max_redemptions_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
