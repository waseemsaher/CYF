<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseContentController extends Controller
{
    /**
     * Get course content outline or full learning materials based on enrollment status.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        /** @var Course $course */
        $course = Course::query()
            ->with(['sections.items.quiz'])
            ->where('slug', $slug)
            ->firstOrFail();

        /** @var User|null $user */
        $user = $request->user();

        $isUnlocked = false;
        if ($user !== null) {
            $isStaff = $user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage');
            if ($isStaff) {
                $isUnlocked = true;
            } else {
                $isUnlocked = Enrollment::query()
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->where('status', 'active')
                    ->where(function ($query): void {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->exists();
            }
        }

        $sectionsData = [];
        foreach ($course->sections as $section) {
            $itemsData = [];

            foreach ($section->items as $item) {
                if (! $item->is_published && ! ($user?->hasRole(['superadmin', 'admin']) ?? false)) {
                    continue;
                }

                $itemData = [
                    'id' => $item->id,
                    'type' => $item->type,
                    'title' => $item->getTranslations('title'),
                    'position' => $item->position,
                    'is_locked' => ! $isUnlocked,
                ];

                if ($isUnlocked) {
                    $itemData['description'] = $item->getTranslations('description');
                    $itemData['url'] = $item->url;
                    $itemData['has_file'] = $item->file_path !== null;
                    if ($item->quiz !== null) {
                        $itemData['quiz'] = [
                            'id' => $item->quiz->id,
                            'kind' => $item->quiz->kind,
                            'title' => $item->quiz->getTranslations('title'),
                            'duration_minutes' => $item->quiz->duration_minutes,
                            'max_attempts' => $item->quiz->max_attempts,
                            'available_from' => $item->quiz->available_from?->toIso8601String(),
                            'available_until' => $item->quiz->available_until?->toIso8601String(),
                        ];
                    }
                }

                $itemsData[] = $itemData;
            }

            $sectionsData[] = [
                'id' => $section->id,
                'title' => $section->getTranslations('title'),
                'position' => $section->position,
                'items' => $itemsData,
            ];
        }

        return response()->json([
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'slug' => $course->slug,
                    'title' => $course->getTranslations('title'),
                    'telegram_invite_link' => $isUnlocked ? $course->telegram_invite_link : null,
                ],
                'is_unlocked' => $isUnlocked,
                'sections' => $sectionsData,
            ],
        ]);
    }

    /**
     * Download or stream a private course file for an enrolled student.
     */
    public function downloadFile(Request $request, string $slug, int $itemId): StreamedResponse|JsonResponse
    {
        /** @var Course $course */
        $course = Course::query()->where('slug', $slug)->firstOrFail();

        /** @var User $user */
        $user = $request->user();

        $isStaff = $user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage');
        if (! $isStaff) {
            $isEnrolled = Enrollment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->exists();

            if (! $isEnrolled) {
                return response()->json(['message' => 'Unauthorized. Active enrollment required.'], 403);
            }
        }

        /** @var CourseItem $item */
        $item = CourseItem::query()
            ->where('id', $itemId)
            ->where('course_id', $course->id)
            ->firstOrFail();

        if (! $item->file_path || ! Storage::disk('local')->exists($item->file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        $filename = basename($item->file_path);

        return Storage::disk('local')->download($item->file_path, $filename);
    }
}
