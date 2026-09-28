<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\CourseItem;
use App\Models\CourseSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContentImportCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'content:import
                            {file : Path to the JSON file describing courses, sections, and items}
                            {--dry-run : Validate and simulate the import without writing to the database}';

    /**
     * @var string
     */
    protected $description = 'Import courses, sections, and items from a JSON file with strict validation';

    public function handle(): int
    {
        $filePath = (string) $this->argument('file');

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            $this->error("File does not exist or is not readable: [{$filePath}]");

            return self::FAILURE;
        }

        $rawContent = file_get_contents($filePath);
        if ($rawContent === false) {
            $this->error("Failed to read file content: [{$filePath}]");

            return self::FAILURE;
        }

        $data = json_decode($rawContent, true);
        if (! is_array($data)) {
            $this->error('Invalid JSON structure: Root must be a valid JSON object.');

            return self::FAILURE;
        }

        $validator = Validator::make($data, [
            'courses' => ['required', 'array', 'min:1'],
            'courses.*.slug' => ['required', 'string', 'max:255'],
            'courses.*.title' => ['required'],
            'courses.*.description' => ['nullable'],
            'courses.*.price_cents' => ['nullable', 'integer', 'min:0'],
            'courses.*.status' => ['nullable', 'in:draft,published,archived'],
            'courses.*.telegram_chat_id' => ['nullable'],
            'courses.*.telegram_invite_link' => ['nullable', 'string', 'url'],
            'courses.*.sections' => ['nullable', 'array'],
            'courses.*.sections.*.title' => ['required'],
            'courses.*.sections.*.position' => ['nullable', 'integer', 'min:0'],
            'courses.*.sections.*.items' => ['nullable', 'array'],
            'courses.*.sections.*.items.*.type' => ['required', 'in:link,lecture_link,external_link,file,text,quiz,exam'],
            'courses.*.sections.*.items.*.title' => ['required'],
            'courses.*.sections.*.items.*.url' => ['nullable', 'string'],
            'courses.*.sections.*.items.*.description' => ['nullable'],
            'courses.*.sections.*.items.*.position' => ['nullable', 'integer', 'min:0'],
            'courses.*.sections.*.items.*.published' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            $this->error('Validation errors found in import file:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;
        }

        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('Executing in DRY-RUN mode. No changes will be persisted to the database.');
        }

        $coursesData = $data['courses'];
        $createdCourses = 0;
        $updatedCourses = 0;
        $createdSections = 0;
        $updatedSections = 0;
        $createdItems = 0;
        $updatedItems = 0;

        $summaryRows = [];

        DB::beginTransaction();

        try {
            foreach ($coursesData as $courseItem) {
                $slug = (string) $courseItem['slug'];
                $title = is_array($courseItem['title']) ? $courseItem['title'] : ['ar' => (string) $courseItem['title'], 'en' => (string) $courseItem['title']];
                $description = isset($courseItem['description'])
                    ? (is_array($courseItem['description']) ? $courseItem['description'] : ['ar' => (string) $courseItem['description'], 'en' => (string) $courseItem['description']])
                    : ['ar' => '', 'en' => ''];
                $priceCents = (int) ($courseItem['price_cents'] ?? 0);
                $status = (string) ($courseItem['status'] ?? 'published');
                $telegramChatId = isset($courseItem['telegram_chat_id'])
                    ? (int) $courseItem['telegram_chat_id']
                    : null;
                $telegramInviteLink = isset($courseItem['telegram_invite_link']) ? (string) $courseItem['telegram_invite_link'] : null;

                $existingCourse = Course::withTrashed()->where('slug', $slug)->first();
                $isNewCourse = $existingCourse === null;

                if (! $isDryRun) {
                    if ($isNewCourse) {
                        $course = new Course;
                        $course->slug = $slug;
                    } else {
                        $course = $existingCourse;
                        if ($course->trashed()) {
                            $course->restore();
                        }
                    }

                    $course->setTranslations('title', $title);
                    $course->setTranslations('description', $description);
                    $course->price_cents = $priceCents;
                    $course->status = $status;
                    $course->telegram_group_id = $telegramChatId;
                    $course->telegram_channel_id = $telegramChatId;
                    $course->telegram_invite_link = $telegramInviteLink;
                    $course->save();
                } else {
                    $course = $existingCourse ?? new Course(['slug' => $slug]);
                }

                if ($isNewCourse) {
                    $createdCourses++;
                } else {
                    $updatedCourses++;
                }

                $sections = $courseItem['sections'] ?? [];
                foreach ($sections as $secIndex => $secData) {
                    $secTitle = is_array($secData['title']) ? $secData['title'] : ['ar' => (string) $secData['title'], 'en' => (string) $secData['title']];
                    $secPos = (int) ($secData['position'] ?? ($secIndex + 1));

                    $existingSec = $course->id ? CourseSection::where('course_id', $course->id)->where('position', $secPos)->first() : null;
                    $isNewSec = $existingSec === null;

                    if (! $isDryRun && $course->id) {
                        $section = $existingSec ?? new CourseSection;
                        $section->course_id = $course->id;
                        $section->setTranslations('title', $secTitle);
                        $section->position = $secPos;
                        $section->save();
                    } else {
                        $section = $existingSec ?? new CourseSection(['position' => $secPos]);
                    }

                    if ($isNewSec) {
                        $createdSections++;
                    } else {
                        $updatedSections++;
                    }

                    $items = $secData['items'] ?? [];
                    foreach ($items as $itemIndex => $itData) {
                        $itTitle = is_array($itData['title']) ? $itData['title'] : ['ar' => (string) $itData['title'], 'en' => (string) $itData['title']];
                        $itDesc = isset($itData['description'])
                            ? (is_array($itData['description']) ? $itData['description'] : ['ar' => (string) $itData['description'], 'en' => (string) $itData['description']])
                            : null;
                        $itPos = (int) ($itData['position'] ?? ($itemIndex + 1));
                        $rawType = (string) $itData['type'];
                        $itType = match ($rawType) {
                            'link' => 'lecture_link',
                            default => $rawType,
                        };
                        $itUrl = $itData['url'] ?? null;
                        $itPublished = (bool) ($itData['published'] ?? true);

                        $existingItem = ($course->id && $section->id)
                            ? CourseItem::withTrashed()->where('course_id', $course->id)->where('section_id', $section->id)->where('position', $itPos)->first()
                            : null;
                        $isNewItem = $existingItem === null;

                        if (! $isDryRun && $course->id && $section->id) {
                            $item = $existingItem ?? new CourseItem;
                            if ($item->trashed()) {
                                $item->restore();
                            }
                            $item->course_id = $course->id;
                            $item->section_id = $section->id;
                            $item->type = $itType;
                            $item->setTranslations('title', $itTitle);
                            if ($itDesc !== null) {
                                $item->setTranslations('description', $itDesc);
                            }
                            $item->url = $itUrl;
                            $item->position = $itPos;
                            $item->is_published = $itPublished;
                            $item->save();
                        }

                        if ($isNewItem) {
                            $createdItems++;
                        } else {
                            $updatedItems++;
                        }
                    }
                }

                $summaryRows[] = [
                    'Slug' => $slug,
                    'Title (AR)' => $title['ar'] ?? '',
                    'Sections' => count($sections),
                    'Action' => $isNewCourse ? 'Created' : 'Updated',
                ];
            }

            if ($isDryRun) {
                DB::rollBack();
            } else {
                DB::commit();

                activity('content')
                    ->withProperties([
                        'file' => basename($filePath),
                        'created_courses' => $createdCourses,
                        'updated_courses' => $updatedCourses,
                        'created_sections' => $createdSections,
                        'updated_sections' => $updatedSections,
                        'created_items' => $createdItems,
                        'updated_items' => $updatedItems,
                    ])
                    ->log('content_imported');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import failed with exception: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Slug', 'Title (AR)', 'Sections', 'Action'], $summaryRows);

        $actionPrefix = $isDryRun ? '[Dry Run] Would ' : '';
        $this->info("{$actionPrefix}Import summary:");
        $this->line("  Courses:  {$createdCourses} created, {$updatedCourses} updated");
        $this->line("  Sections: {$createdSections} created, {$updatedSections} updated");
        $this->line("  Items:    {$createdItems} created, {$updatedItems} updated");

        return self::SUCCESS;
    }
}
