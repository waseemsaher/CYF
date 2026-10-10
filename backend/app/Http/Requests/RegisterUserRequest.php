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
     * Prepare the data for validation with sanitization.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $name = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $this->name)) ?? '');
            $data['name'] = $name;
        }

        if ($this->has('email')) {
            $data['email'] = strtolower(trim((string) $this->email));
        }

        if ($this->has('phone')) {
            $phone = (string) $this->phone;
            $arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $westernDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $phone = str_replace($arabicDigits, $westernDigits, $phone);
            $phone = str_replace($persianDigits, $westernDigits, $phone);
            $phone = preg_replace('/\D/', '', $phone) ?? '';
            if (str_starts_with($phone, '0020') && strlen($phone) === 14) {
                $phone = '0'.substr($phone, 4);
            } elseif (str_starts_with($phone, '20') && strlen($phone) === 12) {
                $phone = '0'.substr($phone, 2);
            } elseif (str_starts_with($phone, '20') && strlen($phone) === 13) {
                $phone = substr($phone, 2);
            } elseif (strlen($phone) === 10 && in_array(substr($phone, 0, 2), ['10', '11', '12', '15'], true)) {
                $phone = '0'.$phone;
            }
            $data['phone'] = $phone;
        }

        if ($this->has('telegram_username')) {
            $tg = trim((string) $this->telegram_username);
            $tg = preg_replace('#^https?://t\.me/#i', '', $tg) ?? '';
            $tg = ltrim($tg, '@');
            $tg = trim($tg);
            $data['telegram_username'] = $tg === '' ? null : $tg;
        }

        if ($this->has('academic_year')) {
            $data['academic_year'] = trim(strip_tags((string) $this->academic_year));
        }

        if ($this->has('department')) {
            $data['department'] = trim(strip_tags((string) $this->department));
        }

        if (! empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[\p{L}\s\.\-\']+$/u'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'branch' => ['required', 'in:azhar_boys,azhar_girls'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'academic_year' => ['nullable', 'string', 'max:50'],
            'department' => ['required', 'string', 'max:100'],
            'telegram_username' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_]{5,32}$/'],
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
            'name.required' => __('الاسم بالكامل مطلوب.'),
            'name.min' => __('يجب ألا يقل الاسم عن 3 أحرف.'),
            'name.regex' => __('الاسم يجب أن يحتوي على حروف فقط بدون أرقام أو رموز خاصة.'),
            'email.required' => __('البريد الإلكتروني مطلوب.'),
            'email.email' => __('يرجى إدخال بريد إلكتروني صالح.'),
            'email.unique' => __('هذا البريد الإلكتروني مسجل بالفعل.'),
            'password.required' => __('كلمة المرور مطلوبة.'),
            'password.min' => __('يجب ألا تقل كلمة المرور عن 8 أحرف.'),
            'password.confirmed' => __('كلمة المرور وتأكيدها غير متطابقين.'),
            'telegram_username.regex' => __('اسم مستخدم تليجرام يجب أن يتكون من 5 إلى 32 حرفاً أو رقماً بالإنجليزية (بدون مسافات).'),
            'phone.required' => __('رقم محفظتك الإلكترونية مطلوب لاسترداد أي مبلغ عند الحاجة.'),
            'phone.regex' => __('رقم المحفظة يجب أن يكون رقم موبايل مصري صحيح مكوّن من 11 رقمًا (مثال: 01012345678).'),
        ];
    }
}
