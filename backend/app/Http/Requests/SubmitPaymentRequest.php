<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class SubmitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxSizeKb = (int) Setting::getValue('uploads', 'proof_max_size_kb', 5120);

        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'method' => ['required', 'string', 'max:100'],
            'sender_identifier' => ['required', 'string', 'max:255'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', "max:{$maxSizeKb}"],
            'student_note' => ['nullable', 'string', 'max:1000'],
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
            'sender_identifier.required' => __('يجب إدخال رقم أو حساب المرسل.'),
            'proof.required' => __('يجب رفع صورة إثبات الدفع.'),
            'proof.mimes' => __('يجب أن تكون صورة الإثبات بصيغة JPG أو PNG أو WebP.'),
            'proof.max' => __('حجم صورة الإثبات يتجاوز الحد المسموح.'),
        ];
    }
}
