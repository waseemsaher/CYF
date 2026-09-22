@component('mail::message')
@if($locale === 'ar')
# تمت الموافقة على دفعتك

مرحبًا {{ $user->getAttribute('name') }}،

تمت الموافقة على دفعتك بنجاح للدورة **{{ $course->getTranslation('title', 'ar') }}**.

**تفاصيل الدفع:**
- المبلغ: {{ number_format($payment->getAttribute('amount_due_cents') / 100, 2) }} جنيه
- طريقة الدفع: {{ $payment->getAttribute('method') }}

يمكنك الآن الوصول إلى محتوى الدورة من حسابك.

@component('mail::button', ['url' => config('app.frontend_url') . '/my-courses'])
الذهاب لدوراتي
@endcomponent

شكرًا لك،
فريق FCAI Courses

@else
# Your Payment Has Been Approved

Hello {{ $user->getAttribute('name') }},

Your payment for the course **{{ $course->getTranslation('title', 'en') }}** has been approved.

**Payment Details:**
- Amount: {{ number_format($payment->getAttribute('amount_due_cents') / 100, 2) }} EGP
- Payment Method: {{ $payment->getAttribute('method') }}

You can now access the course content from your account.

@component('mail::button', ['url' => config('app.frontend_url') . '/my-courses'])
Go to My Courses
@endcomponent

Thanks,
FCAI Courses Team
@endif
@endcomponent
