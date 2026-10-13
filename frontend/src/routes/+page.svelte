<script lang="ts">
  import { page } from '$app/stores';
  import { onMount } from 'svelte';
  import { t } from '$lib/i18n';
  import {
    FOUNDER_TRUST_CONFIG,
    getSafeConfig
  } from '$lib/pricing.config';
  import { resolveCtaSlot, resolveOptionalCtaSlot } from '$lib/api/ctas';
  import { HOME_SAMPLE_LESSON, getSampleLessonForCourse } from '$lib/data/sample-lessons';
  import { WHATSAPP_SUPPORT_URL } from '$lib/socials';
  import TestimonialsSection from '$lib/components/TestimonialsSection.svelte';

  export let data;

  let videoEl: HTMLVideoElement;
  let videoLoaded = false;

  onMount(() => {
    if (videoEl) {
      videoEl.play().catch(() => {
        // Fallback to poster if autoplay is blocked
      });
    }
  });

  // Validated, placeholder-safe pricing & review configuration
  $: safeConfig = getSafeConfig();

  // Sample lessons (computed server-side in loader, with safe fallback)
  $: configuredSampleLessons =
    data.configuredSampleLessons ?? [
      ...(HOME_SAMPLE_LESSON ? [HOME_SAMPLE_LESSON] : []),
      ...(data.courses ?? [])
        .map((course: { slug: string }) => getSampleLessonForCourse(course.slug))
        .filter((item): item is NonNullable<typeof item> => Boolean(item))
    ];
  $: hasSampleLessons = data.hasSampleLessons ?? configuredSampleLessons.length > 0;

  // Admin-editable CTAs: required slots fall back safely; optional heroSecondaryCta renders nothing if inactive or pointing to hidden #sample-lesson
  $: heroPrimaryCta = data.heroPrimaryCta ?? resolveCtaSlot(data.ctas, 'home_hero_primary');
  $: heroSecondaryCta =
    data.heroSecondaryCta !== undefined
      ? data.heroSecondaryCta
      : resolveOptionalCtaSlot(data.ctas, 'home_hero_secondary', { hasSampleLessons });
  $: homeBottomCta = data.homeBottomCta ?? resolveCtaSlot(data.ctas, 'home_bottom');

  // Dynamic courses count for stats bar
  $: coursesCountStat =
    data.totalCoursesCount && data.totalCoursesCount > 0
      ? `${data.totalCoursesCount} مقررات متاحة`
      : 'فرقة أولى والباقي جاي';

  // Steps data (honest manual receipt review on step 3, no Telegram icon)
  $: steps = [
    {
      num: '1',
      title: $t.home.steps.step1Title,
      desc: $t.home.steps.step1Desc,
      icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'
    },
    {
      num: '2',
      title: $t.home.steps.step2Title,
      desc: $t.home.steps.step2Desc,
      icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'
    },
    {
      num: '3',
      title: $t.home.steps.step3Title,
      desc: $t.home.steps.step3Desc,
      icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
    }
  ];

  const featureIcons = [
    {
      icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
      color: 'from-brand-cyan/20 to-brand-cyan/5',
      iconColor: 'text-brand-cyan'
    },
    {
      icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
      color: 'from-emerald-500/20 to-emerald-500/5',
      iconColor: 'text-emerald-400'
    },
    {
      icon: 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z',
      color: 'from-purple-500/20 to-purple-500/5',
      iconColor: 'text-purple-400'
    },
    {
      icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
      color: 'from-amber-500/20 to-amber-500/5',
      iconColor: 'text-amber-400'
    }
  ];

  $: features = $t.home.features.items.map((item, idx) => ({
    title: item.title,
    desc: item.desc,
    ...(featureIcons[idx] ?? featureIcons[0])
  }));

  // FAQ items: always rendered in DOM for crawlers and GEO citation
  $: faqs = $t.home.faq.items;

  let openFaqIndex: number | null = 0;
  function toggleFaq(index: number) {
    openFaqIndex = openFaqIndex === index ? null : index;
  }

  // JSON-LD FAQPage schema
  $: faqJsonLd = `<script type="application/ld+json">${JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faqs.map((item) => ({
      '@type': 'Question',
      name: item.q,
      acceptedAnswer: {
        '@type': 'Answer',
        text: item.a
      }
    }))
  })}<\/script>`;

  function getCtaButtonClass(style: string, variant: 'hero' | 'bottom' = 'hero'): string {
    if (style === 'primary') {
      return 'group inline-flex items-center justify-center gap-2 rounded-full bg-brand-cyan px-8 py-4 text-base font-extrabold text-brand-navy shadow-lg shadow-brand-cyan/25 transition-all duration-300 hover:-translate-y-0.5 hover:bg-white hover:shadow-xl';
    }
    if (style === 'secondary') {
      return variant === 'bottom'
        ? 'inline-flex items-center justify-center gap-2 rounded-full border border-white/25 bg-white/10 px-8 py-4 text-base font-bold text-white backdrop-blur-sm transition-all duration-300 hover:bg-white/20'
        : 'inline-flex items-center justify-center gap-2 rounded-full border border-white/20 bg-white/10 px-8 py-4 text-base font-bold text-white backdrop-blur-sm transition-all duration-300 hover:border-white/40 hover:bg-white/15';
    }
    return 'inline-flex items-center justify-center gap-2 rounded-full border-2 border-brand-cyan px-8 py-4 text-base font-bold text-brand-cyan transition-all duration-300 hover:bg-brand-cyan hover:text-brand-navy';
  }
</script>

<svelte:head>
  <title>{$t.home.metaTitle}</title>
  <meta name="description" content={$t.home.metaDesc} />
  <meta property="og:title" content={$t.home.metaTitle} />
  <meta property="og:description" content={$t.home.metaDesc} />
  <meta name="twitter:title" content={$t.home.metaTitle} />
  <meta name="twitter:description" content={$t.home.metaDesc} />
  <link rel="preload" as="image" href="/images/hero-poster.webp" fetchpriority="high" />
  <!-- eslint-disable-next-line svelte/no-at-html-tags -->
  {@html faqJsonLd}
</svelte:head>

<div class="overflow-hidden font-sans selection:bg-brand-cyan selection:text-brand-navy">

  <!-- ============================================ -->
  <!-- HERO SECTION                                 -->
  <!-- ============================================ -->
  <section class="relative min-h-[82vh] flex items-center justify-center overflow-hidden bg-brand-navy">
    <div class="absolute inset-0 z-0">
      <img
        src="/images/hero-poster.webp"
        alt="منصة Codeera - كوديرا لطلاب كلية الحاسبات والذكاء الاصطناعي جامعة الأزهر"
        width="1920"
        height="1080"
        fetchpriority="high"
        class="h-full w-full object-cover object-center transition-opacity duration-1000 {videoLoaded ? 'opacity-0' : 'opacity-50'}"
      />
      <video
        bind:this={videoEl}
        on:canplay={() => (videoLoaded = true)}
        src="/videos/hero-bg.mp4"
        poster="/images/hero-poster.webp"
        autoplay
        loop
        muted
        playsinline
        preload="metadata"
        class="absolute inset-0 h-full w-full object-cover object-center transition-opacity duration-1000 {videoLoaded ? 'opacity-50' : 'opacity-0'}"
      ></video>
      <div class="absolute inset-0 bg-gradient-to-b from-brand-navy/65 via-brand-navy/75 to-brand-navy"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-brand-navy/80 via-transparent to-brand-navy/80"></div>
    </div>

    <div class="pointer-events-none absolute -top-32 right-1/4 h-[420px] w-[420px] rounded-full bg-brand-cyan/10 blur-[120px]"></div>
    <div class="pointer-events-none absolute -bottom-32 left-1/4 h-[360px] w-[360px] rounded-full bg-brand-blue/15 blur-[100px]"></div>

    <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center">
      <!-- Badge -->
      <div class="mb-7 inline-flex items-center gap-2.5 rounded-full border border-brand-cyan/30 bg-brand-cyan/10 px-5 py-2 backdrop-blur-md">
        <span class="relative flex h-2 w-2">
          <span class="relative inline-flex h-2 w-2 rounded-full bg-brand-cyan"></span>
        </span>
        <span class="text-xs sm:text-sm font-bold text-brand-cyan tracking-wide">{$t.hero.badge}</span>
      </div>

      <!-- Single H1 on homepage -->
      <h1 class="mb-6 text-3xl font-extrabold leading-[1.3] tracking-tight text-white sm:text-5xl lg:text-6xl">
        {$t.hero.titlePrefix}
        <span class="block mt-2 bg-gradient-to-l from-brand-cyan via-cyan-300 to-white bg-clip-text text-transparent">
          {$t.hero.titleHighlight}
        </span>
      </h1>

      <!-- Subtitle -->
      <p class="mx-auto mb-10 max-w-2xl text-base leading-relaxed text-slate-300 sm:text-lg lg:text-xl font-normal">
        {$t.hero.subheading}
      </p>

      <!-- Admin-editable CTA Buttons (Primary + Secondary) -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
        <a
          href={heroPrimaryCta.url}
          target={heroPrimaryCta.open_in_new_tab ? '_blank' : undefined}
          rel={heroPrimaryCta.open_in_new_tab ? 'noopener noreferrer' : undefined}
          class={getCtaButtonClass(heroPrimaryCta.style, 'hero')}
        >
          <span>{heroPrimaryCta.label_ar}</span>
          <svg class="h-5 w-5 transition-transform duration-300 group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </a>

        {#if heroSecondaryCta}
          <a
            href={heroSecondaryCta.url}
            target={heroSecondaryCta.open_in_new_tab ? '_blank' : undefined}
            rel={heroSecondaryCta.open_in_new_tab ? 'noopener noreferrer' : undefined}
            class={getCtaButtonClass(heroSecondaryCta.style, 'hero')}
          >
            {heroSecondaryCta.label_ar}
          </a>
        {/if}
      </div>

      <!-- Honest Stats Bar (true numbers + config values only) -->
      <div class="mt-16 grid grid-cols-2 sm:grid-cols-4 gap-4 mx-auto max-w-3xl rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-md">
        <div class="text-center">
          <div class="text-lg sm:text-xl font-extrabold text-brand-cyan">{coursesCountStat}</div>
          <div class="mt-1 text-xs font-medium text-slate-300">حاسبات الأزهر</div>
        </div>
        <div class="text-center">
          <div class="text-sm sm:text-base font-extrabold text-white">{safeConfig.reviewTimeText}</div>
          <div class="mt-1 text-xs font-medium text-slate-300">مراجعة يدوية للإيصال</div>
        </div>
        <div class="text-center">
          <div class="text-sm sm:text-base font-extrabold text-brand-cyan">مبني على توصيف مقرر فرقتك</div>
          <div class="mt-1 text-xs font-medium text-slate-300">تنظيم حسب القسم</div>
        </div>
        <div class="text-center">
          <div class="text-sm sm:text-base font-extrabold text-white">فودافون كاش / انستاباي</div>
          <div class="mt-1 text-xs font-medium text-slate-300">بتدفع على رقم رسمي وبتستلم تأكيد</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================ -->
  <!-- HOW IT WORKS - 3 STEPS                       -->
  <!-- ============================================ -->
  <section id="how-it-works" class="relative bg-slate-50 py-24 px-4">
    <div class="mx-auto max-w-6xl">
      <div class="mb-16 text-center">
        <span class="mb-3 inline-block rounded-full bg-brand-navy/10 px-4 py-1 text-xs font-extrabold text-brand-navy">
          {$t.home.steps.eyebrow}
        </span>
        <h2 class="text-3xl font-extrabold text-brand-navy sm:text-4xl">{$t.home.steps.title}</h2>
      </div>

      <div class="grid gap-8 md:grid-cols-3 relative">
        <div class="hidden md:block absolute top-16 right-[16%] left-[16%] h-0.5 bg-gradient-to-l from-brand-cyan/30 via-brand-navy/20 to-brand-cyan/30"></div>

        {#each steps as step}
          <div class="group relative flex flex-col items-center text-center">
            <div class="relative z-10 mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-brand-navy text-white shadow-xl shadow-brand-navy/20 transition-all duration-300 group-hover:-translate-y-1 group-hover:bg-brand-blue group-hover:shadow-2xl">
              <svg class="h-8 w-8 text-brand-cyan" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d={step.icon} />
              </svg>
              <span class="absolute -top-2 -right-2 flex h-7 w-7 items-center justify-center rounded-full bg-brand-cyan text-xs font-extrabold text-brand-navy shadow-md">
                {step.num}
              </span>
            </div>

            <h3 class="mb-2 text-xl font-bold text-brand-navy">{step.title}</h3>
            <p class="max-w-xs text-sm leading-relaxed text-slate-600">{step.desc}</p>
          </div>
        {/each}
      </div>
    </div>
  </section>

  <!-- ============================================ -->
  <!-- FREE SAMPLE LESSON (DATA-DRIVEN, OPTIONAL)   -->
  <!-- ============================================ -->
  {#if hasSampleLessons}
    <section id="sample-lesson" class="relative bg-white py-20 px-4 border-b border-slate-100">
      <div class="mx-auto max-w-5xl">
        <div class="mb-10 text-center">
          <span class="mb-3 inline-block rounded-full bg-brand-cyan/15 px-4 py-1 text-xs font-extrabold text-brand-navy">
            جرّب الشرح بنفسك
          </span>
          <h2 class="text-3xl font-extrabold text-brand-navy sm:text-4xl">درس تجريبي مجاني</h2>
          <p class="mx-auto mt-2 max-w-xl text-sm text-slate-600">
            شوف طريقة الشرح وتنظيم الأفكار قبل ما تقرر تشترك في أي مقرر.
          </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
          {#each configuredSampleLessons as sample (sample.courseSlug + sample.titleAr)}
            <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50 p-6 shadow-sm">
              <div>
                <div class="mb-2 flex items-center justify-between gap-2">
                  <span class="rounded-full bg-brand-navy px-3 py-1 text-xs font-bold text-brand-cyan">درس تجريبي</span>
                  {#if sample.durationMinutes || sample.durationText}
                    <span class="text-xs font-medium text-slate-500">
                      {sample.durationText || `${sample.durationMinutes} دقيقة`}
                    </span>
                  {/if}
                </div>
                <h3 class="mb-2 text-lg font-extrabold text-brand-navy">{sample.titleAr}</h3>
                {#if sample.descriptionAr}
                  <p class="mb-4 text-sm leading-relaxed text-slate-600">{sample.descriptionAr}</p>
                {/if}
              </div>
              <a
                href={sample.videoUrl}
                target={sample.videoUrl.startsWith('https://') ? '_blank' : undefined}
                rel={sample.videoUrl.startsWith('https://') ? 'noopener noreferrer' : undefined}
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-brand-blue"
              >
                <span>شغّل الدرس التجريبي</span>
                <svg class="h-4 w-4 text-brand-cyan" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                </svg>
              </a>
            </div>
          {/each}
        </div>
      </div>
    </section>
  {/if}

  <!-- ============================================ -->
  <!-- FEATURED COURSES & HONEST PRICING            -->
  <!-- ============================================ -->
  <section class="relative bg-white py-24 px-4">
    <div class="mx-auto max-w-6xl">
      <div class="mb-14 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 border-b border-slate-100 pb-6">
        <div>
          <span class="mb-2 inline-block rounded-full bg-brand-cyan/15 px-3.5 py-1 text-xs font-extrabold text-brand-navy">
            {$t.home.courses.eyebrow}
          </span>
          <h2 class="text-3xl font-extrabold text-brand-navy sm:text-4xl">{$t.home.courses.title}</h2>
          <p class="mt-1 text-base text-slate-600">مواد مترتبة حسب فرقتك وقسمك في كلية الحاسبات والذكاء الاصطناعي جامعة الأزهر</p>
        </div>
        <a
          href="/courses"
          class="group inline-flex items-center gap-2 self-start sm:self-auto rounded-full border border-slate-200 bg-slate-50 px-5 py-2.5 text-sm font-bold text-brand-navy transition-all hover:border-brand-navy hover:bg-brand-navy hover:text-white"
        >
          <span>{$t.home.courses.viewAll}</span>
          <svg class="h-4 w-4 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </a>
      </div>

      <!-- Pricing & Bundle Summary Banner (all values from pricing.config via getSafeConfig) -->
      <div class="mb-12 grid gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-6 md:grid-cols-2 {safeConfig.isBundleValid ? 'lg:grid-cols-3' : 'lg:grid-cols-2'}">
        <div class="rounded-xl bg-white p-5 border border-slate-200/80 shadow-sm">
          <span class="text-xs font-bold text-slate-500">اشتراك المادة الواحدة</span>
          <div class="mt-1 flex items-baseline gap-1.5">
            {#if safeConfig.isEarlyBirdValid}
              <span class="text-2xl font-black text-brand-navy">{safeConfig.earlyBirdPrice}</span>
              <span class="text-sm font-bold text-slate-600">{$t.home.courses.currency}</span>
              <span class="mr-2 text-sm text-slate-400 line-through">{safeConfig.coursePriceEgp} {$t.home.courses.currency}</span>
            {:else}
              <span class="text-2xl font-black text-brand-navy">{safeConfig.coursePriceEgp}</span>
              <span class="text-sm font-bold text-slate-600">{$t.home.courses.currency}</span>
            {/if}
          </div>
          {#if safeConfig.isEarlyBirdValid && safeConfig.earlyBirdDateLabel}
            <p class="mt-2 inline-block rounded-md bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 border border-amber-200">
              السعر ده لحد {safeConfig.earlyBirdDateLabel} قبل الميد
            </p>
          {:else}
            <p class="mt-2 text-xs text-slate-600">شامل الشرح، الكويزات، ومتابعة الأسئلة ({safeConfig.subscriptionValidityText})</p>
          {/if}
        </div>

        {#if safeConfig.isBundleValid}
          <div class="rounded-xl bg-brand-navy p-5 text-white shadow-md">
            <div class="flex items-center justify-between gap-2">
              <span class="text-xs font-bold text-brand-cyan">باقة {safeConfig.bundleCoursesCount} مواد</span>
              {#if safeConfig.bundleSavingEgp > 0}
                <span class="rounded-full bg-brand-cyan px-2.5 py-0.5 text-xs font-extrabold text-brand-navy">
                  وفّر {safeConfig.bundleSavingEgp} {$t.home.courses.currency}
                </span>
              {/if}
            </div>
            <div class="mt-1 flex items-baseline gap-1.5">
              <span class="text-2xl font-black text-white">{safeConfig.bundlePrice}</span>
              <span class="text-sm font-bold text-slate-300">{$t.home.courses.currency}</span>
              <span class="mr-2 text-xs text-slate-400 line-through">{safeConfig.coursePriceEgp * safeConfig.bundleCoursesCount} {$t.home.courses.currency}</span>
            </div>
            <p class="mt-2 text-xs text-slate-300">
              اختار أي {safeConfig.bundleCoursesCount} مواد في فرقتك ونسّق تفعيل الباقة مع الدعم بعد التحويل.
            </p>
          </div>
        {/if}

        <div class="flex flex-col justify-between rounded-xl bg-white p-5 border border-slate-200/80 shadow-sm {safeConfig.isBundleValid ? 'md:col-span-2 lg:col-span-1' : ''}">
          <div>
            <span class="text-xs font-bold text-emerald-700">سياسة استرجاع واضحة</span>
            <p class="mt-1.5 text-xs leading-relaxed text-slate-600">
              {safeConfig.refundGuaranteeShort}
            </p>
          </div>
          <a href="/refund" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-brand-navy hover:text-brand-blue underline underline-offset-4">
            اقرأ تفاصيل سياسة الاسترجاع كاملة
          </a>
        </div>
      </div>

      {#if data.error}
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center text-sm font-medium text-amber-800">
          {data.error}
        </div>
      {:else if data.courses.length === 0}
        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50/50 py-16 text-center">
          <svg class="mx-auto mb-4 h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
          <p class="text-base font-medium text-slate-500">{$t.home.courses.emptyNotice}</p>
        </div>
      {:else}
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
          {#each data.courses as course}
            <a
              href={`/courses/${course.slug}`}
              class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-brand-cyan/40 hover:shadow-xl hover:shadow-brand-navy/8"
            >
              <div class="relative aspect-[16/10] w-full overflow-hidden bg-slate-100">
                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-navy to-brand-blue p-6 text-center">
                  <span class="text-lg font-bold text-white/90 leading-snug">{course.title?.ar || course.slug}</span>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
              </div>

              <div class="flex flex-1 flex-col p-6">
                <div class="mb-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                  {#if course.lessons_count && course.lessons_count > 0}
                    <span class="rounded-md bg-slate-100 px-2.5 py-0.5 font-semibold text-slate-700">{course.lessons_count} درس</span>
                  {/if}
                  {#if course.quizzes_count && course.quizzes_count > 0}
                    <span class="rounded-md bg-slate-100 px-2.5 py-0.5 font-semibold text-slate-700">{course.quizzes_count} كويز</span>
                  {/if}
                </div>

                <h3 class="mb-2 text-lg font-extrabold text-brand-navy transition-colors group-hover:text-brand-blue line-clamp-1">
                  {course.title?.ar || course.slug}
                </h3>
                <p class="mb-6 line-clamp-2 text-sm leading-relaxed text-slate-500 flex-1">
                  {course.description?.ar || 'محتوى دراسي مبني على توصيف مقرر فرقتك مع كويزات دورية.'}
                </p>

                <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-4">
                  <div>
                    <span class="text-xs font-medium text-slate-400 block">{$t.home.courses.priceCaption}</span>
                    <span class="text-xl font-black text-brand-navy">
                      {course.amount_due_cents > 0 ? Math.round(course.amount_due_cents / 100) : safeConfig.coursePriceEgp}
                      <span class="text-xs font-bold text-slate-500">{$t.home.courses.currency}</span>
                    </span>
                  </div>
                  <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-navy px-4 py-2 text-xs font-bold text-white transition-all group-hover:bg-brand-cyan group-hover:text-brand-navy">
                    <span>{$t.home.courses.details}</span>
                    <svg class="h-3.5 w-3.5 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                  </span>
                </div>
              </div>
            </a>
          {/each}
        </div>
      {/if}
    </div>
  </section>

  <!-- ============================================ -->
  <!-- WHY Codeera - FEATURES                       -->
  <!-- ============================================ -->
  <section class="relative overflow-hidden bg-brand-navy py-24 px-4 text-white">
    <div class="pointer-events-none absolute top-0 left-1/3 h-96 w-96 rounded-full bg-brand-cyan/5 blur-[120px]"></div>
    <div class="pointer-events-none absolute bottom-0 right-1/4 h-80 w-80 rounded-full bg-brand-blue/10 blur-[100px]"></div>

    <div class="relative z-10 mx-auto max-w-6xl">
      <div class="mb-16 text-center">
        <span class="mb-3 inline-block rounded-full border border-brand-cyan/30 bg-brand-cyan/10 px-4 py-1 text-xs font-extrabold text-brand-cyan">
          {$t.home.features.eyebrow}
        </span>
        <h2 class="text-3xl font-extrabold text-white sm:text-4xl">{$t.home.features.title}</h2>
      </div>

      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {#each features as feat}
          <div class="group relative rounded-2xl border border-white/10 bg-white/[0.04] p-7 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-brand-cyan/30 hover:bg-white/[0.08]">
            <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br {feat.color}">
              <svg class="h-7 w-7 {feat.iconColor}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d={feat.icon} />
              </svg>
            </div>
            <h3 class="mb-2 text-lg font-bold text-white">{feat.title}</h3>
            <p class="text-sm leading-relaxed text-slate-300">{feat.desc}</p>
          </div>
        {/each}
      </div>
    </div>
  </section>

  <!-- ============================================ -->
  <!-- WHY TRUST US (FOUNDER STORY + REFUND + WA)   -->
  <!-- ============================================ -->
  <section class="relative bg-white py-24 px-4 border-b border-slate-100">
    <div class="mx-auto max-w-5xl">
      <div class="mb-12 text-center">
        <span class="mb-3 inline-block rounded-full bg-brand-navy/10 px-4 py-1 text-xs font-extrabold text-brand-navy">
          {FOUNDER_TRUST_CONFIG.eyebrow}
        </span>
        <h2 class="text-3xl font-extrabold text-brand-navy sm:text-4xl">{FOUNDER_TRUST_CONFIG.title}</h2>
      </div>

      <div class="grid gap-8 md:grid-cols-2">
        <!-- Founder / Developer Story Card -->
        <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50 p-7 shadow-sm">
          <div>
            <div class="mb-4 inline-flex items-center gap-3">
              <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-navy text-brand-cyan font-extrabold text-lg">
                &lt;/&gt;
              </div>
              <div>
                <h3 class="text-lg font-extrabold text-brand-navy">{FOUNDER_TRUST_CONFIG.headlineAr}</h3>
                <p class="text-xs font-semibold text-slate-500">{FOUNDER_TRUST_CONFIG.roleAr}</p>
              </div>
            </div>
            {#if safeConfig.founderStory}
              <p class="text-sm leading-relaxed text-slate-700">
                {safeConfig.founderStory}
              </p>
            {/if}
            <p class="mt-3 text-xs font-semibold text-slate-600">
              {FOUNDER_TRUST_CONFIG.paymentSafetyNote}
            </p>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-200/80 flex items-center justify-between text-xs text-slate-500">
            <span>عندك سؤال قبل ما تشترك؟</span>
            <a
              href={WHATSAPP_SUPPORT_URL}
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1.5 font-extrabold text-emerald-700 hover:text-emerald-800"
            >
              <span>اسألنا على واتساب قبل ما تشترك</span>
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </a>
          </div>
        </div>

        <!-- Refund Guarantee & Transparency Card -->
        <div class="flex flex-col justify-between rounded-2xl border border-emerald-200/80 bg-emerald-50/40 p-7 shadow-sm">
          <div>
            <div class="mb-4 inline-flex items-center gap-3">
              <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-600 text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-extrabold text-brand-navy">ضمان استرجاع واضح</h3>
                <p class="text-xs font-semibold text-emerald-800">حقك محفوظ بشروط واضحة ومكتوبة</p>
              </div>
            </div>

            <p class="text-sm leading-relaxed text-slate-700">
              {FOUNDER_TRUST_CONFIG.refundSummary}
            </p>

            <ul class="mt-4 space-y-2 text-xs text-slate-600">
              <li class="flex items-center gap-2">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                <span>مراجعة يدوية لكل إيصال دفع ({safeConfig.reviewTimeText}).</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                <span>استرجاع كامل لو الإيصال اترفض أو حصل خطأ في التحويل.</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                <span>{safeConfig.refundWindowRuleAr}</span>
              </li>
            </ul>
          </div>

          <div class="mt-6 pt-4 border-t border-emerald-200/60 flex flex-wrap items-center justify-between gap-3">
            <a
              href="/refund"
              class="inline-flex items-center gap-1.5 rounded-xl bg-brand-navy px-4 py-2.5 text-xs font-bold text-white transition hover:bg-brand-blue"
            >
              <span>اقرأ سياسة الاسترجاع كاملة</span>
            </a>
            <a
              href={WHATSAPP_SUPPORT_URL}
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-700/30 bg-white px-4 py-2.5 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50"
            >
              <span>تواصل عبر واتساب</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================ -->
  <!-- TESTIMONIALS (HIDDEN WHEN EMPTY)             -->
  <!-- ============================================ -->
  <TestimonialsSection />

  <!-- ============================================ -->
  <!-- FAQ SECTION (VISIBLE IN DOM FOR CRAWLERS)    -->
  <!-- ============================================ -->
  <section id="faq" class="bg-slate-50 py-24 px-4">
    <div class="mx-auto max-w-3xl">
      <div class="mb-14 text-center">
        <span class="mb-3 inline-block rounded-full bg-brand-navy/10 px-4 py-1 text-xs font-extrabold text-brand-navy">
          {$t.home.faq.eyebrow}
        </span>
        <h2 class="text-3xl font-extrabold text-brand-navy sm:text-4xl">{$t.home.faq.title}</h2>
      </div>

      <div class="space-y-3">
        {#each faqs as faq, index}
          <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white transition-all duration-200 {openFaqIndex === index ? 'shadow-md border-brand-cyan/40' : 'shadow-sm'}">
            <h3 class="m-0">
              <button
                type="button"
                aria-expanded={openFaqIndex === index}
                aria-controls={`faq-answer-${index}`}
                on:click={() => toggleFaq(index)}
                class="flex w-full items-center justify-between px-6 py-5 text-right font-bold text-brand-navy transition-colors hover:text-brand-blue"
              >
                <span class="text-base">{faq.q}</span>
                <svg
                  class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 mr-4 {openFaqIndex === index ? 'rotate-180 text-brand-cyan' : ''}"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
            </h3>
            <div
              id={`faq-answer-${index}`}
              class="grid transition-all duration-200 ease-in-out {openFaqIndex === index ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'}"
            >
              <div class="overflow-hidden">
                <div class="border-t border-slate-100 px-6 pb-5 pt-3 text-sm leading-relaxed text-slate-600">
                  {faq.a}
                </div>
              </div>
            </div>
          </div>
        {/each}
      </div>
    </div>
  </section>

  <!-- ============================================ -->
  <!-- BOTTOM CTA BANNER (ADMIN-EDITABLE)           -->
  <!-- ============================================ -->
  {#if !$page.data.user}
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-navy via-brand-blue to-brand-navy py-20 px-4 text-center text-white">
      <div class="pointer-events-none absolute -top-20 right-1/3 h-64 w-64 rounded-full bg-brand-cyan/15 blur-[80px]"></div>

      <div class="relative z-10 mx-auto max-w-2xl">
        <h2 class="mb-4 text-3xl font-extrabold sm:text-4xl">
          {homeBottomCta.headline_ar || $t.home.cta.title}
        </h2>
        <p class="mb-8 text-base text-slate-300 sm:text-lg">
          {homeBottomCta.subtext_ar || $t.home.cta.desc}
        </p>
        <a
          href={homeBottomCta.url}
          target={homeBottomCta.open_in_new_tab ? '_blank' : undefined}
          rel={homeBottomCta.open_in_new_tab ? 'noopener noreferrer' : undefined}
          class={getCtaButtonClass(homeBottomCta.style, 'bottom')}
        >
          <span>{homeBottomCta.label_ar}</span>
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </a>
      </div>
    </section>
  {/if}

</div>
