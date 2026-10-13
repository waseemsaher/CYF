<script lang="ts">
  import { getSafeConfig, getValidityText } from '$lib/pricing.config';

  export let data: {
    block?: {
      title?: { ar?: string; en?: string };
      body?: { ar?: string; en?: string };
    } | null;
  };

  const safeConfig = getSafeConfig();
  const validityText = getValidityText();

  const defaultTitle = 'شروط الاستخدام';
  const defaultBody = `أهلاً بك في منصة Codeera - كوديرا. باستخدامك للمنصة أنت بتوافق على الشروط البسيطة دي عشان نحافظ على حقك وحق زمايلك:
1) الحساب شخصي لطالب واحد فقط، وممنوع مشاركة بيانات الدخول أو تحميل أو إعادة نشر الفيديوهات والملخصات خارج المنصة.
2) الدفع بيتم عن طريق فودافون كاش أو إنستاباي على الأرقام الرسمية الموضحة في صفحة الدفع، وبيتم تفعيل الاشتراك بعد مراجعة الإيصال يدوياً من فريق الإدارة (${safeConfig.reviewTimeText}).
3) صلاحية الاشتراك في المقرر بتستمر ${validityText} وفق نظام الاشتراك الحالي.
4) استرداد الرسوم متاح خلال ${safeConfig.refundWindowDays} أيام من تاريخ تفعيل المقرر بدون شرط على نسبة المشاهدة، مع احتفاظ الفريق بحق مراجعة حالات سوء الاستخدام الواضحة.
5) Codeera - كوديرا منصة تعليمية مستقلة وغير تابعة رسمياً لجامعة الأزهر.`;

  $: title = data?.block?.title?.ar || defaultTitle;
  $: body = data?.block?.body?.ar || defaultBody;
</script>

<svelte:head>
  <title>شروط الاستخدام | حاسبات الأزهر | Codeera - كوديرا</title>
  <meta
    name="description"
    content="شروط وأحكام استخدام منصة Codeera - كوديرا التعليمية لطلاب كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر (الفرقة الأولى وباقي الفرق)."
  />
</svelte:head>

<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8" dir="rtl">
  <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm sm:p-12">
    <h1 class="mb-6 border-b border-slate-100 pb-6 text-2xl font-extrabold text-brand-navy sm:text-3xl">
      {title}
    </h1>
    <div class="prose prose-slate max-w-none leading-loose text-slate-700 whitespace-pre-line">
      {body}
    </div>
  </div>
</div>
