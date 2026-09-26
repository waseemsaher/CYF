<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Models\Course;
use App\Models\CourseItem;
use App\Models\CourseSection;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            $maxSizeKb = (int) Setting::getValue('uploads', 'teacher_file_max_size_kb', 51200);

            if ($file->getSize() > $maxSizeKb * 1024) {
                throw ValidationException::withMessages([
                    'file' => [__('The file size exceeds the allowed limit.')],
                ]);
            }

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

            // Strip EXIF metadata from images if PNG or JPEG
            if (in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                $image = match ($mimeType) {
                    'image/jpeg' => @imagecreatefromjpeg($file->getRealPath()),
                    default => @imagecreatefrompng($file->getRealPath()),
                };

                if ($image !== false) {
                    $randomName = bin2hex(random_bytes(16)).'.'.($mimeType === 'image/jpeg' ? 'jpg' : 'png');
                    $filePath = "course_files/{$course->id}/{$randomName}";
                    $tempPath = tempnam(sys_get_temp_dir(), 'course_img_');
                    if ($tempPath !== false) {
                        if ($mimeType === 'image/jpeg') {
                            imagejpeg($image, $tempPath, 90);
                        } else {
                            imagepng($image, $tempPath, 9);
                        }
                        imagedestroy($image);
                        Storage::disk('local')->put($filePath, (string) file_get_contents($tempPath));
                        unlink($tempPath);
                    }
                }
            }

            if ($filePath === null) {
                $filePath = $file->store("course_files/{$course->id}", 'local');
            }
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
