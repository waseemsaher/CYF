@component('mail::message')
@if($locale === 'ar')
# تم رفض دفعتك

مرحبًا {{ $user->getAttribute('name') }}،

للأسف تم رفض دفعتك للدورة **{{ $course->getTranslation('title', 'ar') }}**.

**سبب الرفض:**
{{ $reason }}

يمكنك تقديم دفعة جديدة مع إثبات دفع صحيح من صفحة الدورة.

@component('mail::button', ['url' => config('app.frontend_url') . '/courses/' . $course->getAttribute('slug')])
العودة للدورة
@endcomponent

شكرًا لك،
فريق FCAI Courses

@else
# Your Payment Has Been Rejected

Hello {{ $user->getAttribute('name') }},

Unfortunately, your payment for the course **{{ $course->getTranslation('title', 'en') }}** has been rejected.

**Reason:**
{{ $reason }}

You can submit a new payment with valid proof from the course page.

@component('mail::button', ['url' => config('app.frontend_url') . '/courses/' . $course->getAttribute('slug')])
Go to Course
@endcomponent

Thanks,
FCAI Courses Team
@endif
@endcomponent
