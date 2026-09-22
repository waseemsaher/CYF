<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('courses', 'slug')],
            'title' => ['required', 'array', 'required_array_keys:ar,en'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],
            'description' => ['required', 'array', 'required_array_keys:ar,en'],
            'description.ar' => ['required', 'string'],
            'description.en' => ['required', 'string'],
            'cover_image_path' => ['nullable', 'string', 'max:255'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'telegram_chat_id' => ['nullable', 'integer'],
            'telegram_invite_link' => ['nullable', 'url', 'max:255'],
            'teacher_share_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
