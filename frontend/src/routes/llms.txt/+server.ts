import type { RequestHandler } from './$types';
import { getCourses } from '$lib/api/catalog';
import { getSafeConfig } from '$lib/pricing.config';

export const GET: RequestHandler = async ({ fetch }) => {
  const safe = getSafeConfig();
  let coursesSection =
    '- برمجة 1، تفاضل وتكامل، إلكترونيات، ومواد الفرقة الأولى بكلية الحاسبات والذكاء الاصطناعي جامعة الأزهر.';
  try {
    const res = await getCourses(fetch, new URLSearchParams());
    const published = (res.data ?? []).filter((c) => !c.status || c.status === 'published');
    if (published.length > 0) {
      coursesSection = published
        .map(
          (c) =>
            `- [${c.title?.ar || c.slug}](https://codeera.tech/courses/${c.slug}): ${
              c.description?.ar || 'مقرر دراسي لطلاب حاسبات الأزهر'
            } (${Math.round((c.amount_due_cents || c.price_cents || safe.coursePriceEgp * 100) / 100)} EGP)`
        )
        .join('\n');
    }
  } catch {
    // Use fallback summary if API is unreachable during build
  }

  const bundleLine = safe.isBundleValid
    ? `\n- **باقة ${safe.bundleCoursesCount} مواد**: ${safe.bundlePrice} جنيه مصري (توفير ${safe.bundleSavingEgp} جنيه مقارنة بالاشتراك المنفرد).`
    : '';

  const content = `# Codeera - كوديرا (https://codeera.tech)

> منصة تعليمية إلكترونية باللغة العربية مخصصة لطلاب كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر في مصر (الفرقة الأولى وباقي الفرق الدراسية).
> ملاحظة: Codeera منصة مستقلة وغير تابعة رسمياً لجامعة الأزهر.

## عن المنصة (About Codeera)
- **الاسم الرسمي**: Codeera - كوديرا
- **الجمهور المستهدف**: طلاب كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر (مصر)، مرتبة حسب الفرقة الدراسية والقسم الأكاديمي.
- **ما تقدمه المنصة**: شرح منظم للمحاضرات مبني على توصيف مقرر الفرقة، كويزات تقيس مستواك مع مراجعة الإجابات الصحيحة، وتقدر تسأل الفريق على واتساب أو في جروب المادة.

## الأسعار وطريقة الاشتراك (Pricing & Enrollment)
- **سعر المادة الواحدة**: ${safe.coursePriceEgp} جنيه مصري (EGP).${bundleLine}
- **فترة صلاحية الاشتراك**: ${safe.subscriptionValidityText}.
- **وسائل الدفع**: فودافون كاش (Vodafone Cash) أو إنستاباي (InstaPay) على الأرقام الرسمية الموضحة في صفحة الدفع، يليها رفع صورة الإيصال (Screenshot).
- **مراجعة وتفعيل الاشتراك**: تتم مراجعة الإيصالات يدوياً من إدارة المنصة (${safe.reviewTimeText}) ويُفتح المحتوى فور التأكيد.
- **سياسة الاسترجاع**: ${safe.refundGuaranteeShort} ${safe.refundMisuseClauseAr}

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
