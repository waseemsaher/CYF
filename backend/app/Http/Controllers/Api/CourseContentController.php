<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Telegram\Actions\SendCourseInviteLink;
use App\Domain\Telegram\Services\TelegramUrlService;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\TelegramCourseInvite;
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
        $user = $request->user('sanctum') ?? $request->user();

        $isUnlocked = false;
        if ($user !== null) {
            $isStaff = $user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage');
            $isTeacher = $user->hasRole('teacher') && $course->teachers()->where('users.id', $user->id)->exists();
            if ($isStaff || $isTeacher) {
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

                $isItemFree = (bool) $item->is_free;
                $itemUnlocked = $isUnlocked || $isItemFree;

                $itemData = [
                    'id' => $item->id,
                    'type' => $item->type,
                    'title' => $item->getTranslations('title'),
                    'position' => $item->position,
                    'is_locked' => ! $itemUnlocked,
                    'is_free' => $isItemFree,
                ];

                if ($itemUnlocked) {
                    $itemData['description'] = $item->getTranslations('description');

                    $url = $item->url;
                    if (! $url && $item->telegram_message_id) {
                        /** @var TelegramUrlService $telegramUrlService */
                        $telegramUrlService = app(TelegramUrlService::class);
                        $url = $telegramUrlService->forLesson($item);
                    }

                    $itemData['url'] = $url;
                    $itemData['has_file'] = $item->file_path !== null;
                    $itemData['telegram_message_id'] = $item->telegram_message_id;
                    $itemData['has_telegram_video'] = $item->telegram_message_id !== null;
                    if ($item->quiz !== null && $isUnlocked) {
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

        $telegramInviteLink = null;
        if ($isUnlocked) {
            if ($user !== null) {
                $userInvite = TelegramCourseInvite::query()
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->where('expires_at', '>', now())
                    ->whereNull('used_at')
                    ->latest('id')
                    ->value('invite_link');

                $telegramInviteLink = $userInvite ?: $course->telegram_invite_link;

                if (! $telegramInviteLink && $user->telegram_user_id && ($course->telegram_channel_id || $course->telegram_group_id)) {
                    try {
                        app(SendCourseInviteLink::class)->handle($user, $course);
                        $telegramInviteLink = TelegramCourseInvite::query()
                            ->where('user_id', $user->id)
                            ->where('course_id', $course->id)
                            ->where('expires_at', '>', now())
                            ->whereNull('used_at')
                            ->latest('id')
                            ->value('invite_link');
                    } catch (\Throwable $e) {
                        // Non-blocking
                    }
                }
            } else {
                $telegramInviteLink = $course->telegram_invite_link;
            }
        }

        return response()->json([
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'slug' => $course->slug,
                    'title' => $course->getTranslations('title'),
                    'telegram_invite_link' => $telegramInviteLink,
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

        /** @var User|null $user */
        $user = $request->user('sanctum') ?? $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized. Active enrollment required.'], 403);
        }

        $isStaff = $user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage');
        $isTeacher = $user->hasRole('teacher') && $course->teachers()->where('users.id', $user->id)->exists();
        if (! $isStaff && ! $isTeacher) {
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

        $disk = config('filesystems.default', 'local');
        if (! $item->file_path || ! Storage::disk($disk)->exists($item->file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        $filename = basename($item->file_path);

        return Storage::disk($disk)->download($item->file_path, $filename);
    }
}
