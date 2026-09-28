<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ContentBlock;
use Illuminate\Database\Seeder;

class ContentBlockSeeder extends Seeder
{
    public function run(): void
    {
        $blocks = [
            [
                'key' => 'landing.hero',
                'group' => 'landing',
                'content' => [
                    'ar' => 'منصة Codeera التعليمية. مسارك الأكاديمي المباشر نحو التفوق البرمجي.',
                    'en' => 'Codeera Learning Platform. Your direct path to excellence in computing and programming.',
                ],
            ],
            [
                'key' => 'landing.why_us',
                'group' => 'landing',
                'content' => [
                    'ar' => 'محتوى دراسي منظم ومصور، مجموعات تليجرام مقفولة وموثقة، شروحات وافية واختبارات تفاعلية مستمرة.',
                    'en' => 'Structured computing curriculum, verified closed Telegram groups, and continuous interactive quizzes.',
                ],
            ],
            [
                'key' => 'landing.faq',
                'group' => 'landing',
                'content' => [
                    'ar' => "س: كيف يمكنني الانضمام لمجموعة التليجرام للمادة؟\nج: بعد تأكيد اشتراكك، اربط حساب التليجرام الخاص بك عبر المنصة واضغط على رابط الانضمام وسيتم قبولك فورياً.",
                    'en' => "Q: How do I join the course Telegram group?\nA: After your payment is approved, link your Telegram account on the platform and send a join request.",
                ],
            ],
            [
                'key' => 'legal.terms',
                'group' => 'legal',
                'content' => [
                    'ar' => "هذه الصفحة قيد الإعداد والمراجعة، وسيتم نشر شروط الاستخدام المعتمدة قبل فتح المنصة للاستخدام العام للطلاب.\nإذا كان لديك أي استفسار، يرجى التواصل مع إدارة المنصة مباشرة.",
                    'en' => "This page is currently being prepared and reviewed. The finalized terms of use will be published before the platform opens for general student use.\nIf you have any questions, please contact the platform administration directly.",
                ],
            ],
            [
                'key' => 'legal.privacy',
                'group' => 'legal',
                'content' => [
                    'ar' => "هذه الصفحة قيد الإعداد والمراجعة، وسيتم نشر سياسة الخصوصية المعتمدة قبل فتح المنصة للاستخدام العام للطلاب.\nنحن نحرص على حماية بياناتكم الشخصية، ولن يتم مشاركتها مع أي جهة خارجية.",
                    'en' => "This page is currently being prepared and reviewed. The finalized privacy policy will be published before the platform opens for general student use.\nWe are committed to protecting your personal information.",
                ],
            ],
            [
                'key' => 'legal.refund',
                'group' => 'legal',
                'content' => [
                    'ar' => "هذه الصفحة قيد الإعداد والمراجعة، وسيتم نشر سياسة الاسترجاع المعتمدة قبل فتح المنصة للاستخدام العام للطلاب.\nفي حال وجود أي خطأ في السداد أو التحويل، يرجى مراجعة إدارة المنصة لحل المشكلة فوراً.",
                    'en' => "This page is currently being prepared and reviewed. The finalized refund policy will be published before the platform opens for general student use.\nIf you encounter any payment issues, please reach out to the platform administration.",
                ],
            ],
        ];

        foreach ($blocks as $b) {
            ContentBlock::query()->updateOrCreate(
                ['key' => $b['key']],
                [
                    'group' => $b['group'],
                    'content' => $b['content'],
                ]
            );
        }
    }
}
