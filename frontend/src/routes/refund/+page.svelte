<script lang="ts">
  import { WHATSAPP_SUPPORT_URL } from '$lib/socials';
  import { getSafeConfig } from '$lib/pricing.config';

  export let data: {
    block?: {
      title?: { ar?: string; en?: string };
      body?: { ar?: string; en?: string };
    } | null;
  };

  const safeConfig = getSafeConfig();

  const defaultTitle = 'سياسة الاسترجاع والاستبدال';
  const defaultBody = `سياسة الاسترجاع على منصة Codeera - كوديرا واضحة عشان تحفظ حقك:
1) لو إيصال الدفع اترفض لأي سبب أو حصل خطأ في التحويل، بنرجعلك المبلغ بالكامل أو بنساعدك تصحح الطلب.
2) بعد تفعيل الاشتراك، تقدر تطلب استرجاع كامل للمبلغ خلال ${safeConfig.refundWindowDays} أيام من تاريخ التفعيل بشرط ألا تتجاوز نسبة مشاهدتك لمحتوى المقرر ${safeConfig.refundMaxWatchedPercent}%.
3) لطلب الاسترجاع أو الاستفسار، تواصل معانا مباشرة على واتساب برقم تليفونك المسجل وصورة الإيصال، وبيتم رد المبلغ على نفس المحفظة خلال ${safeConfig.refundProcessingDaysText}.`;

  $: title = data?.block?.title?.ar || defaultTitle;
  $: body = data?.block?.body?.ar || defaultBody;
</script>

<svelte:head>
  <title>سياسة الاسترجاع | حاسبات الأزهر | Codeera - كوديرا</title>
  <meta
    name="description"
    content="سياسة الاسترجاع واسترداد رسوم الاشتراك لطلاب حاسبات الأزهر (الفرقة الأولى وباقي الفرق) على منصة Codeera - كوديرا."
  />
</svelte:head>

<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8" dir="rtl">
  <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm sm:p-12">
    <span class="mb-3 inline-block rounded-full bg-emerald-100 px-3.5 py-1 text-xs font-extrabold text-emerald-800">
      ضمان واضح ومكتوب
    </span>
    <h1 class="mb-6 border-b border-slate-100 pb-6 text-2xl font-extrabold text-brand-navy sm:text-3xl">
      {title}
    </h1>

    <div class="prose prose-slate max-w-none leading-loose text-slate-700 whitespace-pre-line">
      {body}
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-6">
      <h2 class="text-lg font-extrabold text-brand-navy">ملخص سريع لشروط الاسترجاع</h2>
      <ul class="mt-3 space-y-2 text-sm text-slate-700">
        <li>• مراجعة إيصالات الدفع بتتم يدوياً ({safeConfig.reviewTimeText}).</li>
        <li>• استرجاع 100% لو الإيصال اترفض أو حصل تحويل بالخطأ.</li>
        <li>• {safeConfig.refundWindowRuleAr}</li>
        <li>• {safeConfig.refundProcessingRuleAr}</li>
      </ul>

      <div class="mt-6 flex flex-wrap items-center gap-3">
        <a
          href={WHATSAPP_SUPPORT_URL}
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-2 rounded-xl bg-brand-navy px-5 py-2.5 text-xs font-bold text-white transition hover:bg-brand-blue"
        >
          <span>تواصل مع الدعم على واتساب</span>
        </a>
        <a
          href="/courses"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-brand-navy transition hover:bg-slate-100"
        >
          <span>تصفح المقررات المتاحة</span>
        </a>
      </div>
    </div>
  </div>
</div>
