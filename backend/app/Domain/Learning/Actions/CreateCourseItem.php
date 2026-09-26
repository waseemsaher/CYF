<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Models\Course;
use App\Models\CourseItem;
use App\Models\CourseSection;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class CreateCourseItem
{
    /**
     * @param  array{ar: string, en: string}  $title
     * @param  array{ar?: string, en?: string}|null  $description
     */
    public function handle(
        Course $course,
        CourseSection $section,
        string $type,
        array $title,
        ?array $description = null,
        ?string $url = null,
        ?UploadedFile $file = null,
        ?int $quizId = null,
        ?int $position = null,
        bool $isPublished = true,
    ): CourseItem {
        $filePath = null;

        if ($type === 'file' && $file !== null) {
            $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'png', 'jpg', 'jpeg', 'mp3', 'mp4'];
            $allowedMimes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'image/png',
                'image/jpeg',
                'audio/mpeg',
                'video/mp4',
            ];

            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();

            if (! in_array($extension, $allowedExtensions, true) || ! in_array($mimeType, $allowedMimes, true)) {
                throw ValidationException::withMessages([
                    'file' => ["نوع الملف غير مسموح به ({$extension})."],
                ]);
            }

            $filePath = $file->store("course_files/{$course->id}", 'local');
        }

        if ($position === null) {
            $maxPos = CourseItem::query()
                ->where('section_id', $section->id)
                ->max('position');
            $position = $maxPos !== null ? ((int) $maxPos + 1) : 0;
        }

        return CourseItem::create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'file_path' => $filePath,
            'quiz_id' => $quizId,
            'position' => $position,
            'is_published' => $isPublished,
        ]);
    }
}
