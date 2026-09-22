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
                    'ar' => 'منصة دورات كلية الحاسبات والمعلومات — جامعة الأزهر. مسارك الأكاديمي المباشر نحو التفوق.',
                    'en' => 'Al-Azhar FCAI Course Platform. Your direct academic path to excellence in computing.',
                ],
            ],
            [
                'key' => 'landing.why_us',
                'group' => 'landing',
                'content' => [
                    'ar' => 'محتوى دراسي مخصص لمناهج الكلية، مجموعات تليجرام مقفولة وموثقة، شروحات وافية واختبارات تفاعلية مستمرة.',
                    'en' => 'Curriculum tailored specifically to Al-Azhar FCAI courses, verified closed Telegram groups, and continuous interactive quizzes.',
                ],
            ],
            [
                'key' => 'landing.faq',
                'group' => 'landing',
                'content' => [
                    'ar' => 'س: كيف يمكنني الانضمام لمجموعة التليجرام للمادة؟\nج: بعد تأكيد اشتراكك، اربط حساب التليجرام الخاص بك عبر المنصة واضغط على رابط الانضمام وسيتم قبولك فورياً.',
                    'en' => 'Q: How do I join the course Telegram group?\nA: After your payment is approved, link your Telegram account on the platform and send a join request.',
                ],
            ],
            [
                'key' => 'legal.terms',
                'group' => 'legal',
                'content' => [
                    'ar' => 'شروط الاستخدام: المنصة مخصصة لطلاب كلية الحاسبات والمعلومات بجامعة الأزهر. يُمنع تسريب المواد أو إعادة نشر الشروحات أو روابط التليجرام لأطراف خارجية دون إذن.',
                    'en' => 'Terms of Use: This platform is exclusively for Al-Azhar FCAI students. Unauthorized redistribution of materials or links is prohibited.',
                ],
            ],
            [
                'key' => 'legal.privacy',
                'group' => 'legal',
                'content' => [
                    'ar' => 'سياسة الخصوصية: نحن نحمي بياناتك الشخصية (الاسم، البريد، العام الأكاديمي، إيصالات الدفع) ولا نشاركها مع أي جهة خارجية مطلقاً.',
                    'en' => 'Privacy Policy: We protect your personal data and payment proofs and never share them with third parties.',
                ],
            ],
            [
                'key' => 'legal.refund',
                'group' => 'legal',
                'content' => [
                    'ar' => 'سياسة الاسترجاع: يمكن للطلاب طلب استرجاع المبلغ في حال تم رفض إيصال الدفع أو قبل فتح المحتوى التعليمي خلال 48 ساعة من السداد.',
                    'en' => 'Refund Policy: Students may request a refund within 48 hours of payment if course materials have not yet been accessed.',
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
