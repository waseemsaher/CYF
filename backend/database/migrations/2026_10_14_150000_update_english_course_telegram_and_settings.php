<?php

declare(strict_types=1);

use App\Models\Course;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $course = Course::where('slug', 'english')->first();
        if ($course) {
            $course->update([
                'telegram_channel_id' => '-1004331858990',
                'telegram_group_id' => '-1005589255780',
                'telegram_invite_link' => 'https://t.me/+D07NyjD8W3E0ZmFk',
                'teacher_share_percent' => 70,
                'is_general' => true,
                'status' => 'published',
            ]);
        } else {
            Course::create([
                'slug' => 'english',
                'title' => [
                    'ar' => 'اللغة الإنجليزية',
                    'en' => 'English',
                ],
                'description' => [
                    'ar' => 'مهارات اللغة الإنجليزية لطلاب الكلية.',
                    'en' => 'English skills for FCAI students.',
                ],
                'price_cents' => 0,
                'status' => 'published',
                'is_general' => true,
                'teacher_share_percent' => 70,
                'telegram_channel_id' => '-1004331858990',
                'telegram_group_id' => '-1005589255780',
                'telegram_invite_link' => 'https://t.me/+D07NyjD8W3E0ZmFk',
                'sort_order' => 5,
            ]);
        }
    }

    public function down(): void
    {
        // No-op
    }
};
