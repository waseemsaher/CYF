<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ContentBlock;
use Illuminate\Database\Seeder;

class ContentBlockSeeder extends Seeder
{
    /**
     * Seed default content blocks safely without overwriting existing admin edits.
     *
     * To intentionally overwrite specific keys in production, either pass `$forceUpdateKeys`
     * (e.g. `['legal.refund']` or `['*']`) or set the `SEED_FORCE_UPDATE_KEYS` env var
     * (comma-separated keys, e.g. `SEED_FORCE_UPDATE_KEYS=legal.refund,landing.hero`).
     *
     * @param  array<int, string>  $forceUpdateKeys
     */
    public function run(array $forceUpdateKeys = []): void
    {
        $blocks = self::defaultBlocks();
        $resolvedForceKeys = self::resolveForceUpdateKeys($forceUpdateKeys);

        foreach ($blocks as $b) {
            $key = $b['key'];
            $attributes = [
                'group' => $b['group'],
                'content' => $b['content'],
            ];

            if (in_array('*', $resolvedForceKeys, true) || in_array($key, $resolvedForceKeys, true)) {
                ContentBlock::query()->updateOrCreate(['key' => $key], $attributes);
            } else {
                ContentBlock::query()->firstOrCreate(['key' => $key], $attributes);
            }
        }
    }

    /**
     * @return array<int, array{key: string, group: string, content: array{ar: string, en: string}}>
     */
    public static function defaultBlocks(): array
    {
        return [
            [
                'key' => 'landing.hero',
                'group' => 'landing',
                'content' => [
                    'ar' => 'مواد حاسبات الأزهر مترتبة حسب فرقتك وقسمك، من الفيديو للكويز في مكان واحد.',
                    'en' => 'Al-Azhar Computing & AI courses organized by your year and department, from video lessons to quizzes in one place.',
                ],
            ],
            [
                'key' => 'landing.why_us',
                'group' => 'landing',
                'content' => [
                    'ar' => 'محتوى مبني على توصيف مقرر فرقتك، كويزات تقيس مستواك مع مراجعة الإجابات الصحيحة، وتقدر تسأل الفريق على واتساب أو في جروب المادة.',
                    'en' => 'Content built around your year syllabus, quizzes with answer reviews, and direct support via WhatsApp or the course group.',
                ],
            ],
            [
                'key' => 'landing.faq',
                'group' => 'landing',
                'content' => [
                    'ar' => "س: إزاي بيتم الاشتراك وتفعيل المقرر؟\nج: بتختار المقرر وبتحول الرسوم على فودافون كاش أو إنستاباي وترفع صورة الإيصال، وفريق الإدارة بيراجع الإيصال يدوياً (غالباً خلال ساعات) وبيفتحلك المحتوى بعد الاعتماد.\n\nس: اشتراك المادة بيفضل شغال معايا لحد إمتى؟\nج: اشتراكك في المقرر بيفضل متاح معاك لحد نهاية الفصل الدراسي الحالي.",
                    'en' => "Q: How does enrollment and course activation work?\nA: Choose your course, transfer fees via Vodafone Cash or InstaPay, upload your receipt screenshot, and our admin team reviews it manually (usually within hours) to unlock your access.\n\nQ: How long is my subscription valid?\nA: Your subscription remains valid until the end of the current academic semester.",
                ],
            ],
            [
                'key' => 'legal.terms',
                'group' => 'legal',
                'content' => [
                    'ar' => "1. طبيعة المنصة:\nCodeera - كوديرا منصة تعليمية مستقلة غير تابعة رسمياً لجامعة الأزهر، بتقدم شروحات وتدريبات ومراجعات مساندة لطلاب كلية الحاسبات والذكاء الاصطناعي.\n\n2. استخدام الحساب:\nالحساب شخصي لطالب واحد فقط، وممنوع مشاركة بيانات الدخول أو تحميل أو إعادة نشر الفيديوهات أو الملخصات خارج المنصة.\n\n3. الاشتراك والتفعيل والصلاحية:\nالاشتراك في المقرر بيستمر لحد نهاية الفصل الدراسي الحالي وفق نظام الاشتراك الحالي. التفعيل بيتم بعد المراجعة اليدوية لإيصال الدفع المرفوع من الطالب (غالباً خلال ساعات).\n\n4. الدعم والتواصل:\nلأي استفسار بخصوص حسابك أو اشتراكك، تقدر تتواصل معانا مباشرة على واتساب الدعم الفني الموضح في المنصة.",
                    'en' => "1. Platform Nature:\nCodeera is an independent educational platform not officially affiliated with Al-Azhar University, providing supplementary explanations and quizzes for FCAI students.\n\n2. Account Usage:\nAccounts are personal to a single student. Sharing credentials or downloading/redistributing materials is prohibited.\n\n3. Enrollment, Activation & Validity:\nCourse access remains valid until the end of the current academic semester under the current subscription plan and activates after manual review of your payment receipt.",
                ],
            ],
            [
                'key' => 'legal.privacy',
                'group' => 'legal',
                'content' => [
                    'ar' => "1. البيانات اللي بنجمعها:\nبنطلب منك البيانات الأساسية بس لتشغيل حسابك وتحديد مقرراتك: الاسم، البريد الإلكتروني، الفرقة والقسم، رقم الموبايل الخاص بالمحفظة، وحساب تليجرام لربط صلاحية مشاهدة الفيديوهات.\n\n2. إزاي بنستخدم بياناتك:\nبنستخدم بياناتك لتفعيل اشتراكك في المواد، مراجعة إيصالات الدفع، وإرسال إشعارات حالة الطلب.\n\n3. حماية الصور والإيصالات:\nصور إيصالات الدفع بتتحفظ في مساحة تخزين خاصة ومؤمنة ومحدش بيطلع عليها غير مسؤول مراجعة المدفوعات، ومش بنشارك بياناتك مع أي طرف تالت.",
                    'en' => "1. Data We Collect:\nWe only collect essential data to run your account: name, email, academic year/department, wallet phone number, and Telegram account for content access.\n\n2. How We Use Data:\nYour data is used solely to verify payments, activate course access, and notify you of subscription updates.\n\n3. Receipt Privacy:\nPayment receipt screenshots are stored on a private disk accessible only to payment reviewers and never shared with third parties.",
                ],
            ],
            [
                'key' => 'legal.refund',
                'group' => 'legal',
                'content' => [
                    'ar' => "سياسة الاسترداد في Codeera - كوديرا واضحة ومباشرة عشان تكون مطمن قبل ما تشترك:\n\n1. في حالة رفض الإيصال أو وجود خطأ في التحويل:\nلو إيصال الدفع اترفض لأي سبب أو حصل خطأ في التحويل ومتمش تفعيل المقرر، المبلغ بيرجعلك بالكامل على نفس رقم المحفظة أو حساب إنستاباي اللي حولت منه.\n\n2. طلب الاسترداد بعد التفعيل:\nخلال 7 أيام من تاريخ تفعيل المقرر، تقدر تطلب استرداد رسوم الاشتراك من غير أي شرط على نسبة المشاهدة.\n\n3. طريقة تقديم طلب الاسترداد:\nلطلب الاسترداد، تواصل معانا على واتساب الدعم الفني برقم تليفونك المسجل وصورة الإيصال، وبيتم مراجعة الطلب ورد المبلغ خلال 2 إلى 3 أيام عمل.\n\n4. مراجعة حالات سوء الاستخدام:\nبيحتفظ فريق المنصة بحق مراجعة الحالات اللي يثبت فيها سوء استخدام واضح للمحتوى (زي تحميل الدروس أو مشاركتها خارج المنصة).",
                    'en' => "Codeera Refund Policy:\n\n1. Rejected Receipt or Transfer Error:\nIf a payment receipt is rejected or there was a transfer error without activating the course, the full amount is returned to your original Vodafone Cash or InstaPay account.\n\n2. Refund After Activation:\nWithin 7 days from the activation date of the course, you can request a full refund of the course fee with no condition on course progress.\n\n3. How to Request a Refund:\nContact our support team via WhatsApp with your registered phone number and receipt screenshot. Refunds are processed within 2 to 3 business days.\n\n4. Misuse Review:\nThe platform team reserves the right to review cases of clear misuse (such as downloading or sharing content outside the platform).",
                ],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $explicitKeys
     * @return array<int, string>
     */
    private static function resolveForceUpdateKeys(array $explicitKeys): array
    {
        $envRaw = (string) env('SEED_FORCE_UPDATE_KEYS', '');
        $envKeys = $envRaw !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $envRaw))))
            : [];

        return array_values(array_unique(array_merge($explicitKeys, $envKeys)));
    }
}
