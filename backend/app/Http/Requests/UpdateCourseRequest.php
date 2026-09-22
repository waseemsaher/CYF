<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
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
        /** @var Course $course */
        $course = $this->route('course');

        return [
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('courses', 'slug')->ignore($course)],
            'title' => ['sometimes', 'array', 'required_array_keys:ar,en'],
            'title.ar' => ['required_with:title', 'string', 'max:255'],
            'title.en' => ['required_with:title', 'string', 'max:255'],
            'description' => ['sometimes', 'array', 'required_array_keys:ar,en'],
            'description.ar' => ['required_with:description', 'string'],
            'description.en' => ['required_with:description', 'string'],
            'cover_image_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'telegram_chat_id' => ['sometimes', 'nullable', 'integer'],
            'telegram_invite_link' => ['sometimes', 'nullable', 'url', 'max:255'],
            'teacher_share_percent' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
