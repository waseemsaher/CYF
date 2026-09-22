<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPaymentMethods();
        $this->seedRevenueShare();
        $this->seedUploadLimits();
        $this->seedEnrollmentSettings();
    }

    private function seedPaymentMethods(): void
    {
        Setting::setValue('payments', 'methods', [
            [
                'key' => 'vodafone_cash',
                'label' => ['ar' => 'فودافون كاش', 'en' => 'Vodafone Cash'],
                'type' => 'ewallet',
                'number' => '01XXXXXXXXX',
                'instructions' => [
                    'ar' => 'حوّل المبلغ المطلوب على رقم فودافون كاش الموضح، ثم ارفع صورة الإيصال.',
                    'en' => 'Transfer the required amount to the Vodafone Cash number shown, then upload the receipt screenshot.',
                ],
                'is_active' => true,
            ],
            [
                'key' => 'instapay',
                'label' => ['ar' => 'إنستاباي', 'en' => 'InstaPay'],
                'type' => 'instapay',
                'number' => 'username@instapay',
                'instructions' => [
                    'ar' => 'حوّل المبلغ المطلوب عبر إنستاباي إلى الحساب الموضح، ثم ارفع صورة الإيصال.',
                    'en' => 'Transfer the required amount via InstaPay to the account shown, then upload the receipt screenshot.',
                ],
                'is_active' => true,
            ],
            [
                'key' => 'other_ewallet',
                'label' => ['ar' => 'محفظة إلكترونية أخرى', 'en' => 'Other e-wallet'],
                'type' => 'ewallet',
                'number' => '',
                'instructions' => [
                    'ar' => 'حوّل المبلغ المطلوب بأي محفظة إلكترونية، ثم ارفع صورة الإيصال مع رقم المحفظة المرسل منها.',
                    'en' => 'Transfer the required amount using any e-wallet, then upload the receipt screenshot with the sender wallet number.',
                ],
                'is_active' => true,
            ],
        ]);
    }

    private function seedRevenueShare(): void
    {
        Setting::setValue('revenue', 'default_teacher_share_percent', 70);
    }

    private function seedUploadLimits(): void
    {
        Setting::setValue('uploads', 'proof_max_size_kb', 5120); // 5 MB
        Setting::setValue('uploads', 'teacher_file_max_size_kb', 51200); // 50 MB
        Setting::setValue('uploads', 'allowed_extensions', ['pdf', 'docx', 'pptx', 'xlsx', 'txt', 'png', 'jpg', 'webp']);
        Setting::setValue('uploads', 'blocked_extensions', ['exe', 'bat', 'cmd', 'sh', 'php', 'js', 'jar', 'msi', 'zip', 'rar', 'tar', '7z']);
    }

    private function seedEnrollmentSettings(): void
    {
        Setting::setValue('enrollment', 'grace_days', 0);
    }
}
