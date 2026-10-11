import type { RequestHandler } from './$types';
import { getCourses } from '$lib/api/catalog';
import {
  COURSE_PRICE_EGP,
  BUNDLE_COURSES_COUNT,
  BUNDLE_PRICE,
  REVIEW_TIME_TEXT,
  SUBSCRIPTION_VALIDITY_TEXT,
  REFUND_GUARANTEE_SHORT
} from '$lib/pricing.config';

export const GET: RequestHandler = async ({ fetch }) => {
  let coursesSection = '- برمجة 1، تفاضل وتكامل، إلكترونيات، ومواد الفرقة الأولى بكلية الحاسبات والذكاء الاصطناعي جامعة الأزهر.';
  try {
    const res = await getCourses(fetch, new URLSearchParams());
    const published = (res.data ?? []).filter((c) => !c.status || c.status === 'published');
    if (published.length > 0) {
      coursesSection = published
        .map(
          (c) =>
            `- [${c.title_ar || c.title?.ar || c.slug}](https://codeera.tech/courses/${c.slug}): ${
              c.description_ar || c.description?.ar || 'مقرر دراسي لطلاب حاسبات الأزهر'
            } (${c.price_egp ?? COURSE_PRICE_EGP} EGP)`
        )
        .join('\n');
    }
  } catch {
    // Use fallback summary if API is unreachable during build
  }

  const content = `# Codeera - كوديرا (https://codeera.tech)

> منصة تعليمية إلكترونية باللغة العربية مخصصة لطلاب كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر في مصر (الفرقة الأولى وباقي الفرق الدراسية).
> ملاحظة: Codeera منصة مستقلة وغير تابعة رسمياً لجامعة الأزهر.

## عن المنصة (About Codeera)
- **الاسم الرسمي**: Codeera - كوديرا
- **الجمهور المستهدف**: طلاب كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر (مصر)، مرتبة حسب الفرقة الدراسية والقسم الأكاديمي.
- **ما تقدمه المنصة**: شرح منظم للمحاضرات مبني على توصيف مقرر الفرقة، كويزات تفاعلية مع توضيح الإجابات، ومتابعة للأسئلة طوال الترم.

## الأسعار وطريقة الاشتراك (Pricing & Enrollment)
- **سعر المادة الواحدة**: ${COURSE_PRICE_EGP} جنيه مصري (EGP).
- **باقة ${BUNDLE_COURSES_COUNT} مواد**: ${BUNDLE_PRICE} جنيه مصري (توفير ${COURSE_PRICE_EGP * BUNDLE_COURSES_COUNT - BUNDLE_PRICE} جنيه مقارنة بالاشتراك المنفرد).
- **فترة صلاحية الاشتراك**: ${SUBSCRIPTION_VALIDITY_TEXT}.
- **وسائل الدفع**: فودافون كاش (Vodafone Cash) أو إنستاباي (InstaPay) على الأرقام الرسمية الموضحة في صفحة الدفع، يليها رفع صورة الإيصال (Screenshot).
- **مراجعة وتفعيل الاشتراك**: تتم مراجعة الإيصالات يدوياً من إدارة المنصة (${REVIEW_TIME_TEXT}) ويُفتح المحتوى فور التأكيد.
- **سياسة الاسترجاع**: ${REFUND_GUARANTEE_SHORT}

## المقررات المتاحة (Course Catalog)
${coursesSection}

## الصفحات الرئيسية (Key Pages)
- الصفحة الرئيسية: https://codeera.tech/
- تصفح جميع المقررات: https://codeera.tech/courses
- سياسة الاسترجاع: https://codeera.tech/refund
- شروط الاستخدام: https://codeera.tech/terms
- سياسة الخصوصية: https://codeera.tech/privacy
`;

  return new Response(content, {
    headers: {
      'Content-Type': 'text/plain; charset=utf-8',
      'Cache-Control': 'public, max-age=1800'
    }
  });
};
