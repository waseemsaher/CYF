<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Enrollment
 */
class EnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Enrollment $enrollment */
        $enrollment = $this->resource;

        return [
            'id' => $enrollment->getKey(),
            'user_id' => $enrollment->getAttribute('user_id'),
            'course_id' => $enrollment->getAttribute('course_id'),
            'term_id' => $enrollment->getAttribute('term_id'),
            'payment_id' => $enrollment->getAttribute('payment_id'),
            'source' => $enrollment->getAttribute('source'),
            'status' => $enrollment->getAttribute('status'),
            'starts_at' => $enrollment->getAttribute('starts_at')?->toISOString(),
            'expires_at' => $enrollment->getAttribute('expires_at')?->toISOString(),
            'granted_by' => $enrollment->getAttribute('granted_by'),
            'course' => $this->whenLoaded('course', function () use ($enrollment): array {
                $course = $enrollment->course;

                return [
                    'id' => $course->getKey(),
                    'slug' => $course->getAttribute('slug'),
                    'title' => $course->getTranslations('title'),
                ];
            }),
            'created_at' => $enrollment->getAttribute('created_at')?->toISOString(),
        ];
    }
}
