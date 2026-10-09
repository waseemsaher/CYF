<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'branch' => ['required', 'in:azhar_boys,azhar_girls'],
            'academic_year' => ['required', 'string', 'max:50'],
            'department' => ['required', 'string', 'max:100'],
            'telegram_username' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^01[0125][0-9]{8}$/'],
            'locale' => ['nullable', 'string', 'in:ar,en'],
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
