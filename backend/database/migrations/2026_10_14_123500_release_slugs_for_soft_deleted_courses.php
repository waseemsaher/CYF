<?php

declare(strict_types=1);

use App\Models\Course;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Course::onlyTrashed()
            ->where('slug', 'not like', '%-deleted-%')
            ->each(function (Course $course): void {
                $suffix = '-deleted-' . time() . '-' . Str::lower(Str::random(6));
                $baseSlug = Str::limit((string) $course->slug, 255 - strlen($suffix), '');
                $course->slug = $baseSlug . $suffix;
                $course->saveQuietly();
            });
    }

    public function down(): void
    {
        // No-op: previously deleted courses retain their modified slugs
    }
};

