<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        Setting::setValue('payments', 'methods', [
            [
                'key' => 'vodafone_cash',
                'label' => ['ar' => 'فودافون كاش', 'en' => 'Vodafone Cash'],
                'type' => 'ewallet',
                'number' => '01009971545',
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
                'number' => '01009971545',
                'instructions' => [
                    'ar' => 'حوّل المبلغ عبر إنستاباي إلى الرقم الموضح، ثم ارفع صورة الإيصال.',
                    'en' => 'Transfer the required amount via InstaPay to the number shown, then upload the receipt screenshot.',
                ],
                'is_active' => true,
            ],
            [
                'key' => 'other_ewallet',
                'label' => ['ar' => 'محفظة إلكترونية أخرى', 'en' => 'Other e-wallet'],
                'type' => 'ewallet',
                'number' => '01009971545',
                'instructions' => [
                    'ar' => 'حوّل المبلغ المطلوب بأي محفظة إلكترونية، ثم ارفع صورة الإيصال مع رقم المحفظة المرسل منها.',
                    'en' => 'Transfer the required amount using any e-wallet, then upload the receipt screenshot with the sender wallet number.',
                ],
                'is_active' => true,
            ],
        ]);
    }

    public function down(): void
    {
        // No-op
    }
};
