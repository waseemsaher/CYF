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
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'locale' => ['sometimes', 'string', 'in:ar,en'],
        ];
    }
}
