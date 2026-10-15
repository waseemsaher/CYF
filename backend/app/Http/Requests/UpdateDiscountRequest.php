<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiscountRequest extends FormRequest
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
            'name' => ['sometimes', 'array', 'required_array_keys:ar,en'],
            'name.ar' => ['required_with:name', 'string', 'max:255'],
            'name.en' => ['required_with:name', 'string', 'max:255'],
            'type' => ['sometimes', 'string', Rule::in(['percent', 'fixed'])],
            'value' => [
                'sometimes',
                'integer',
                'min:1',
                Rule::when(
                    fn (): bool => ($this->input('type') ?? $this->route('discount')?->getAttribute('type')) === 'percent',
                    ['max:100']
                ),
            ],
            'scope' => ['sometimes', 'string', Rule::in(['all', 'courses'])],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'course_ids' => ['sometimes', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
        ];
    }
}
