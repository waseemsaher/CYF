<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'array', 'required_array_keys:ar,en'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['percent', 'fixed'])],
            'value' => [
                'required',
                'integer',
                'min:1',
                Rule::when($this->input('type') === 'percent', ['max:100']),
            ],
            'scope' => ['required', 'string', Rule::in(['all', 'courses'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'course_ids' => ['required_if:scope,courses', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
        ];
    }
}

