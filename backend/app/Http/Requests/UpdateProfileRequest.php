<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'branch' => ['sometimes', 'string', 'in:azhar_boys,azhar_girls'],
            'academic_year' => ['sometimes', 'string', 'max:50'],
            'department' => ['sometimes', 'string', 'max:100'],
            'telegram_username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'regex:/^01[0125][0-9]{8}$/'],
            'locale' => ['sometimes', 'string', 'in:ar,en'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => __('رقم محفظتك الإلكترونية مطلوب لاسترداد أي مبلغ عند الحاجة.'),
            'phone.regex' => __('رقم المحفظة يجب أن يكون رقم موبايل مصري صحيح مكوّن من 11 رقمًا (مثال: 01012345678).'),
        ];
    }
}
