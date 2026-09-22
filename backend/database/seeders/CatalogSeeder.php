<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Term;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAcademicYears();
        $this->seedDepartments();
        $this->seedTerms();
        $this->seedCourses();
    }

    private function seedAcademicYears(): void
    {
        foreach ([
            ['name' => ['ar' => 'السنة الأولى', 'en' => '1st Year'], 'sort_order' => 1],
            ['name' => ['ar' => 'السنة الثانية', 'en' => '2nd Year'], 'sort_order' => 2],
        ] as $year) {
            AcademicYear::query()->updateOrCreate(
                ['name->en' => $year['name']['en']],
                $year,
            );
        }
    }

    private function seedDepartments(): void
    {
        foreach ([
            ['code' => 'CS', 'name' => ['ar' => 'علوم الحاسب', 'en' => 'Computer Science'], 'sort_order' => 1],
            ['code' => 'CY', 'name' => ['ar' => 'الأمن السيبراني', 'en' => 'Cybersecurity'], 'sort_order' => 2],
            ['code' => 'DS', 'name' => ['ar' => 'علم البيانات', 'en' => 'Data Science'], 'sort_order' => 3],
            ['code' => 'AI', 'name' => ['ar' => 'الذكاء الاصطناعي', 'en' => 'Artificial Intelligence'], 'sort_order' => 4],
        ] as $department) {
            Department::query()->updateOrCreate(
                ['code' => $department['code']],
                $department,
            );
        }
    }

    private function seedTerms(): void
    {
        Term::query()->updateOrCreate(
            ['name->en' => 'Term 1'],
            [
                'name' => ['ar' => 'الفصل الدراسي الأول', 'en' => 'Term 1'],
                'starts_at' => '2026-09-22',
                'ends_at' => '2027-01-31',
                'is_current' => true,
                'sort_order' => 1,
            ],
        );
    }

    private function seedCourses(): void
    {
        foreach ([
            ['slug' => 'cpp', 'title' => ['ar' => 'لغة ++C', 'en' => 'C++'], 'description' => ['ar' => 'أساسيات البرمجة بلغة ++C.', 'en' => 'Programming fundamentals with C++.'], 'sort_order' => 1],
            ['slug' => 'discrete-mathematics', 'title' => ['ar' => 'الرياضيات المتقطعة', 'en' => 'Discrete Mathematics'], 'description' => ['ar' => 'مفاهيم المنطق والمجموعات والرسوم البيانية.', 'en' => 'Logic, sets, and graph fundamentals.'], 'sort_order' => 2],
            ['slug' => 'computing-fundamentals', 'title' => ['ar' => 'أساسيات الحاسب', 'en' => 'Computing Fundamentals'], 'description' => ['ar' => 'مدخل إلى مفاهيم الحوسبة الأساسية.', 'en' => 'An introduction to core computing concepts.'], 'sort_order' => 3],
            ['slug' => 'physics', 'title' => ['ar' => 'الفيزياء', 'en' => 'Physics'], 'description' => ['ar' => 'مراجعة موضوعات الفيزياء الأساسية.', 'en' => 'Review of essential physics topics.'], 'sort_order' => 4],
            ['slug' => 'english', 'title' => ['ar' => 'اللغة الإنجليزية', 'en' => 'English'], 'description' => ['ar' => 'مهارات اللغة الإنجليزية لطلاب الكلية.', 'en' => 'English skills for FCAI students.'], 'sort_order' => 5],
        ] as $course) {
            Course::query()->updateOrCreate(
                ['slug' => $course['slug']],
                [
                    ...$course,
                    'price_cents' => 0,
                    'status' => 'published',
                ],
            );
        }
    }
}
