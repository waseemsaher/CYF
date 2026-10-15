<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Setting;
use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('sender_identifier')) {
            $sender = (string) $this->input('sender_identifier');
            $arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $westernDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $sender = str_replace($arabicDigits, $westernDigits, $sender);
            $sender = str_replace($persianDigits, $westernDigits, $sender);
            $this->merge([
                'sender_identifier' => trim($sender),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxSizeKb = (int) Setting::getValue('uploads', 'proof_max_size_kb', 5120);

        $methodsSetting = Setting::getValue('payments', 'methods');
        $allowedMethods = [];
        if (is_array($methodsSetting)) {
            foreach ($methodsSetting as $m) {
                if (is_array($m) && isset($m['key']) && (! isset($m['is_active']) || $m['is_active'])) {
                    $allowedMethods[] = (string) $m['key'];
                }
            }
        } else {
            $allowedMethods = ['vodafone_cash', 'instapay', 'other_ewallet'];
        }

        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'method' => ['required', 'string', 'max:100', Rule::in($allowedMethods)],
            'sender_identifier' => ['required', 'string', 'regex:/^[0-9]{11}$/'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', "max:{$maxSizeKb}"],
            'student_note' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $termId = $this->input('term_id');
                if ($termId && ! $validator->errors()->has('term_id')) {
                    /** @var Term|null $term */
                    $term = Term::find($termId);
                    if ($term) {
                        $graceDays = (int) Setting::getValue('enrollment', 'grace_days', 0);
                        $endsAt = $term->getAttribute('ends_at');
                        $expiresAt = $endsAt?->copy()->addDays($graceDays);
                        if ($expiresAt && ! $expiresAt->isFuture()) {
                            $validator->errors()->add('term_id', __('الفصل الدراسي المختار قد انتهى بالفعل.'));
                        }
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'course_id.required' => __('يجب اختيار الدورة.'),
            'course_id.exists' => __('الدورة المختارة غير موجودة.'),
            'term_id.required' => __('يجب اختيار الفصل الدراسي.'),
            'term_id.exists' => __('الفصل الدراسي المختار غير موجود.'),
            'method.required' => __('يجب اختيار طريقة الدفع.'),
            'method.in' => __('طريقة الدفع المختارة غير صالحة.'),
            'sender_identifier.required' => __('يجب إدخال رقم المرسل.'),
            'sender_identifier.regex' => __('رقم المرسل يجب أن يتكون من 11 رقمًا فقط.'),
            'proof.required' => __('يجب رفع صورة إثبات الدفع.'),
            'proof.mimes' => __('يجب أن تكون صورة الإثبات بصيغة JPG أو PNG أو WebP.'),
            'proof.max' => __('حجم صورة الإثبات يتجاوز الحد المسموح.'),
        ];
    }
}
