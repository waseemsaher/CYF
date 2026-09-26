<script lang="ts">
  import type { PageData } from './$types';
  import HeroCanvas from '$lib/components/HeroCanvas.svelte';
  import HeroTypewriter from '$lib/components/HeroTypewriter.svelte';
  import { currentHeroTranslations, currentLocale } from '$lib/i18n';
  import { currentUser } from '$lib/api/auth';

  let { data }: { data: PageData } = $props();

  const formatPrice = (cents: number) => `${(cents / 100).toLocaleString('ar-EG')} ج.م`;

  let openFaqIndex = $state<number | null>(0);

  function toggleFaq(index: number) {
    openFaqIndex = openFaqIndex === index ? null : index;
  }

  const defaultFaqs = [
    {
      q: 'كيف يمكنني الانضمام لمجموعة التليجرام الخاصة بالمقرر؟',
      a: 'بعد اعتماد إيصال السداد الخاص بك، ادخل إلى صفحة المقرر أو لوحة التحكم واضغط على "ربط حساب تليجرام" لتوليد رابط الدخول الخاص بك. عند إرسال طلب الانضمام لمجموعة المقرر، يقوم بوت المنصة بالتحقق من اشتراكك وقبولك فوراً وبشكل آلي.'
    },
    {
      q: 'ما هي طرق الدفع المتاحة للاشتراك؟',
      a: 'نوفر الدفع السهل والمباشر عبر فودافون كاش (Vodafone Cash)، أو إنستاباي (InstaPay)، أو أي محفظة إلكترونية بنكية. بعد إتمام التحويل، تقوم برفع صورة إيصال التحويل في صفحة إتمام الطلب لتأكيد اشتراكك.'
    },
    {
      q: 'كم يستغرق وقت مراجعة وتأكيد الاشتراك؟',
      a: 'تتم مراجعة إيصالات الدفع بواسطة فريق الإدارة بانتظام وفي أسرع وقت ممكن (عادة خلال دقائق إلى ساعات معدودة). ستصلك رسالة تأكيد عبر البريد الإلكتروني فور الاعتماد مع فتح صلاحيات المادة فوراً.'
    },
    {
      q: 'ما مدة صلاحية اشتراكي في المادة؟',
      a: 'يستمر اشتراكك فعالاً ومتاحاً لك حتى نهاية الفصل الدراسي الرسمي للمقرر، مع فترة سماح إضافية لمراجعة المحتوى والاختبارات حتى انتهاء موسم الامتحانات النهائية.'
    }
  ];
</script>

<svelte:head>
  <title>منصة Codeera | شروحات ومقررات برمجية تفاعلية</title>
  <meta
    name="description"
    content="منصة Codeera التعليمية التفاعلية لشروحات ومقررات واختبارات البرمجة والذكاء الاصطناعي."
  />
</svelte:head>

  <!-- Immersive Tech-Forward Full-Bleed Hero Section -->
  <section class="hero-section" aria-label="مقدمة المنصة">
    <HeroCanvas />
    <div class="hero-overlay" aria-hidden="true"></div>

    <div class="hero-content">
      <div class="badge-pill">
        <span class="badge-pulse" aria-hidden="true"></span>
        <span class="badge-text">{$currentHeroTranslations.badge}</span>
      </div>

      <h1 class="hero-title">
        <span>{$currentHeroTranslations.titlePrefix}</span><br />
        <span class="hero-cyan-gradient">{$currentHeroTranslations.titleHighlight}</span>
      </h1>

      <HeroTypewriter
        prefix={$currentHeroTranslations.typewriterPrefix}
        phrases={$currentHeroTranslations.typewriterPhrases}
      />

      <p class="hero-description">
        {$currentHeroTranslations.subheading}
      </p>

      <div class="hero-actions">
        <a href="/courses" class="btn-hero-primary">
          <span>{$currentHeroTranslations.primaryCta}</span>
          <svg class="hero-btn-arrow" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
        </a>

        {#if $currentUser}
          <a href="/dashboard" class="btn-hero-secondary">
            <span>{$currentHeroTranslations.dashboardCta}</span>
          </a>
        {:else}
          <a href="/register" class="btn-hero-secondary">
            <span>{$currentHeroTranslations.secondaryCta}</span>
          </a>
        {/if}
      </div>

      <!-- Glassmorphic Stats Ribbon -->
      <div class="stats-glass-bar">
        {#each $currentHeroTranslations.stats as stat, i}
          {#if i > 0}
            <div class="stat-separator" aria-hidden="true"></div>
          {/if}
          <div class="stat-pill">
            <div class="stat-icon-wrap" aria-hidden="true">
              {#if i === 0}
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                  <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
              {:else if i === 1}
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
              {:else}
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
              {/if}
            </div>
            <div class="stat-details">
              <strong class="stat-number">{stat.value}</strong>
              <span class="stat-label">{stat.label}</span>
            </div>
          </div>
        {/each}
      </div>
    </div>
  </section>

  <div class="landing-container">
    <!-- How It Works Section -->
    <section class="steps-section" aria-labelledby="steps-title">
    <div class="section-heading-center">
      <span class="eyebrow-pill">خطوات سريعة</span>
      <h2 id="steps-title" class="section-title">كيف تبدأ دراستك معنا؟</h2>
      <p class="section-subtitle">ثلاث خطوات بسيطة ومباشرة تفصلك عن المحتوى الأكاديمي ومجموعات المناقشة.</p>
    </div>

    <div class="steps-grid">
      <div class="step-card">
        <div class="step-header">
          <span class="step-index">01</span>
          <div class="step-icon-wrap">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </div>
        </div>
        <h3 class="step-title">اختر مقررك الدراسي</h3>
        <p class="step-desc">تصفح المقررات المتاحة لفرقتك وقسمك (علوم حاسب، نظم، ذكاء اصطناعي)، واطلع على تفاصيل المنهج وشروحاته.</p>
      </div>

      <div class="step-card">
        <div class="step-header">
          <span class="step-index">02</span>
          <div class="step-icon-wrap">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
              <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
          </div>
        </div>
        <h3 class="step-title">سدد الرسوم وارفع الإيصال</h3>
        <p class="step-desc">حول الرسوم بسهولة عبر فودافون كاش أو إنستاباي، ثم ارفع لقطة شاشة للإيصال في نموذج الاشتراك المباشر.</p>
      </div>

      <div class="step-card featured-step-card">
        <div class="step-header">
          <span class="step-index highlight-index">03</span>
          <div class="step-icon-wrap highlight-icon">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .37z"/>
            </svg>
          </div>
        </div>
        <h3 class="step-title">اربط تليجرام وابدأ الدراسة</h3>
        <p class="step-desc">فور اعتماد الدفع، يفتح لك المحتوى التعليمي فوراً ويتم قبولك آلياً في مجموعة التليجرام عبر البوت الذكي.</p>
      </div>
    </div>
  </section>

  <!-- Featured Courses Section -->
  <section class="courses-section" aria-labelledby="courses-title">
    <div class="section-header-flex">
      <div>
        <span class="eyebrow-pill">المقررات الدراسية</span>
        <h2 id="courses-title" class="section-title">أحدث المقررات المتاحة</h2>
      </div>
      <a href="/courses" class="link-view-all">
        <span>عرض كامل الدليل الأكاديمي</span>
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
      </a>
    </div>

    {#if data.error}
      <div class="notice-card error" role="status">
        <p>{data.error}</p>
      </div>
    {:else if data.courses.length === 0}
      <div class="notice-card">
        <p>ستظهر المقررات الدراسية المتاحة هنا فور إطلاقها.</p>
      </div>
    {:else}
      <div class="courses-grid">
        {#each data.courses as course}
          <article class="course-modern-card">
            <div class="card-top-row">
              <span class="course-slug-badge">{course.slug}</span>
              {#if course.discount_cents > 0}
                <span class="discount-badge">خصم ساري</span>
              {/if}
            </div>

            <div class="card-titles">
              <h3 class="card-title-ar">{course.title.ar}</h3>
              {#if course.title.en}
                <p class="card-title-en">{course.title.en}</p>
              {/if}
            </div>

            <div class="card-bottom-row">
              <div class="price-stack">
                <span class="price-caption">رسوم المقرر</span>
                <div class="price-value-row">
                  {#if course.discount_cents > 0}
                    <span class="strikethrough-price">{formatPrice(course.list_price_cents)}</span>
                  {/if}
                  <strong class="final-price">{formatPrice(course.amount_due_cents)}</strong>
                </div>
              </div>

              <a href={`/courses/${course.slug}`} class="btn-card-explore">
                <span>التفاصيل</span>
                <span aria-hidden="true">←</span>
              </a>
            </div>
          </article>
        {/each}
      </div>
    {/if}
  </section>

  <!-- Platform Features Grid -->
  <section class="features-section" aria-labelledby="features-title">
    <div class="section-heading-center">
      <span class="eyebrow-pill">لماذا Codeera؟</span>
      <h2 id="features-title" class="section-title">بيئة أكاديمية متكاملة لطلاب الحاسبات</h2>
      <p class="section-subtitle">صممت منصة Codeera خصيصاً لتلائم طبيعة ومناهج كلية الحاسبات بجامعة الأزهر.</p>
    </div>

    <div class="features-grid">
      <div class="feature-item">
        <div class="feature-icon-box" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
            <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path>
          </svg>
        </div>
        <h3>تغطية شاملة لمقررات الكلية</h3>
        <p>محتوى مصور ومكتوب متوافق 100% مع توصيف المقررات الأكاديمية والمناهج المعتمدة.</p>
      </div>

      <div class="feature-item">
        <div class="feature-icon-box" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
          </svg>
        </div>
        <h3>انضمام آلي وفوري للمجموعات</h3>
        <p>لا داعي لانتظار قبول المشرفين، بوت المنصة يفحص حالتك ويقبلك تلقائياً فور الاعتماد.</p>
      </div>

      <div class="feature-item">
        <div class="feature-icon-box" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        </div>
        <h3>اختبارات تقييم ذاتي ذكية</h3>
        <p>اختبر معلوماتك بعد كل باب دراسي عبر نظام كويزات تفاعلي يعرض نتيجتك وحلول الأسئلة.</p>
      </div>

      <div class="feature-item">
        <div class="feature-icon-box" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
        </div>
        <h3>دفع آمن ومعالجة مباشرة</h3>
        <p>ادفع عبر المحافظ الإلكترونية المألوفة (فودافون كاش، إنستاباي) مع نظام توثيق فوري للإيصالات.</p>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="faq-section" aria-labelledby="faq-title">
    <div class="section-heading-center">
      <span class="eyebrow-pill">الأسئلة الشائعة</span>
      <h2 id="faq-title" class="section-title">كل ما تريد معرفته عن المنصة</h2>
      <p class="section-subtitle">إجابات واضحة ومباشرة عن كافة تساؤلات الطلاب.</p>
    </div>

    <div class="faq-accordion">
      {#each defaultFaqs as faq, index}
        <div class="faq-card" class:faq-card-open={openFaqIndex === index}>
          <button
            type="button"
            class="faq-trigger"
            onclick={() => toggleFaq(index)}
            aria-expanded={openFaqIndex === index}
          >
            <span class="faq-question">{faq.q}</span>
            <span class="faq-icon-arrow" aria-hidden="true">
              {openFaqIndex === index ? '−' : '+'}
            </span>
          </button>

          {#if openFaqIndex === index}
            <div class="faq-content">
              <p>{faq.a}</p>
            </div>
          {/if}
        </div>
      {/each}
    </div>
  </section>

  <!-- Immersive CTA Banner -->
  <section class="cta-banner">
    <div class="cta-banner-bg" aria-hidden="true"></div>
    <div class="cta-content">
      <span class="cta-badge">ابدأ دراستك الآن</span>
      <h2>جاهز للتفوق في فصلك الدراسي؟</h2>
      <p>انضم لزملائك في كلية الحاسبات والذكاء الاصطناعي واستفد من شروحات المقررات ومجموعات التليجرام المقفولة اليوم.</p>
      <div class="cta-actions">
        <a href="/courses" class="btn-cta-primary">استكشف جميع المقررات</a>
        <a href="/register" class="btn-cta-secondary">إنشاء حساب طالب</a>
      </div>
    </div>
  </section>
</div>

<style>
  .landing-container {
    display: flex;
    flex-direction: column;
    gap: 5rem;
    max-width: 1200px;
    margin-inline: auto;
    padding: 5rem 1.25rem;
  }

  /* ---------------- HERO SECTION (Full-Bleed Layered Video + Particle Canvas) ---------------- */
  .hero-section {
    position: relative;
    overflow: hidden;
    min-height: 100dvh;
    width: 100%;
    margin: 0;
    border: none;
    border-radius: 0;
    box-shadow: none;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(4rem, 8vw, 6rem) clamp(1.5rem, 5vw, 3.5rem);
    box-sizing: border-box;
    background: radial-gradient(135% 120% at 50% 0%, #1E2B4D 0%, #1A1918 100%);
    background-color: var(--text-main, #1A1918);
    color: #FAF8F5;
  }

  :global(:root[data-theme='dark']) .hero-section {
    border: none;
    box-shadow: none;
  }

  /* Semi-transparent Navy-to-Charcoal overlay for guaranteed WCAG AA+ contrast */
  .hero-overlay {
    position: absolute;
    inset: 0;
    background: radial-gradient(
      ellipse at 50% 40%,
      rgba(30, 43, 77, 0.78) 0%,
      rgba(26, 25, 24, 0.92) 100%
    );
    backdrop-filter: blur(2px);
    pointer-events: none;
    z-index: 2;
  }

  .hero-content {
    position: relative;
    z-index: 3;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 1.6rem;
    max-width: 860px;
    margin-inline: auto;
  }

  .badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.4rem 1.1rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1.5px solid rgba(200, 43, 52, 0.45);
    border-radius: 9999px;
    backdrop-filter: blur(8px);
    box-shadow: 0 0 18px rgba(200, 43, 52, 0.18);
  }

  .badge-pulse {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 50%;
    background: var(--brand-accent, #C82B34);
    box-shadow: 0 0 0 3px rgba(200, 43, 52, 0.35);
    animation: pulseGlow 2s infinite ease-in-out;
  }

  @keyframes pulseGlow {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.7; }
  }

  .badge-text {
    font-size: 0.84rem;
    font-weight: 700;
    color: #FAF8F5;
    letter-spacing: 0.01em;
  }

  .hero-title {
    font-size: clamp(2.1rem, 4.5vw, 3.4rem);
    font-weight: 900;
    line-height: 1.25;
    color: #FAF8F5;
    margin: 0;
  }

  .hero-cyan-gradient {
    background: linear-gradient(135deg, #FAF8F5 35%, #E8454F 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    color: #E8454F;
  }

  .hero-description {
    font-size: clamp(1rem, 1.8vw, 1.18rem);
    line-height: 1.8;
    color: #D5CBC1;
    max-width: 700px;
    margin: 0;
  }

  .hero-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.1rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
  }

  .btn-hero-primary {
    background: var(--brand-accent, #C82B34);
    color: #FFFFFF;
    font-weight: 800;
    font-size: 1.05rem;
    padding: 0.85rem 2rem;
    border-radius: 0.75rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    border: 2px solid var(--brand-accent, #C82B34);
    box-shadow: 0 0 25px rgba(200, 43, 52, 0.4);
    transition: transform 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
  }

  .btn-hero-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 35px rgba(200, 43, 52, 0.65);
    background: #B5232B;
  }

  .hero-btn-arrow {
    transition: transform 150ms ease;
  }

  :global([dir="rtl"]) .hero-btn-arrow {
    transform: scaleX(-1);
  }

  :global([dir="rtl"]) .btn-hero-primary:hover .hero-btn-arrow {
    transform: scaleX(-1) translateX(3px);
  }

  :global([dir="ltr"]) .btn-hero-primary:hover .hero-btn-arrow {
    transform: translateX(3px);
  }

  .btn-hero-secondary {
    background: rgba(42, 59, 106, 0.4);
    color: #FAF8F5;
    font-weight: 700;
    font-size: 1.05rem;
    padding: 0.85rem 1.85rem;
    border-radius: 0.75rem;
    text-decoration: none;
    border: 2px solid rgba(213, 203, 193, 0.4);
    backdrop-filter: blur(8px);
    transition: background 150ms ease, border-color 150ms ease, transform 150ms ease, color 150ms ease;
  }

  .btn-hero-secondary:hover {
    background: rgba(42, 59, 106, 0.75);
    border-color: rgba(213, 203, 193, 0.8);
    color: #FFFFFF;
    transform: translateY(-2px);
  }

  /* Glassmorphic Stats Ribbon */
  .stats-glass-bar {
    display: flex;
    align-items: center;
    justify-content: space-around;
    gap: 1.5rem;
    width: 100%;
    max-width: 760px;
    margin-top: 1.25rem;
    padding: 1.25rem 2rem;
    background: rgba(26, 25, 24, 0.65);
    border: 1.5px solid rgba(213, 203, 193, 0.25);
    border-radius: 1.25rem;
    backdrop-filter: blur(16px);
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.4);
  }

  .stat-pill {
    display: flex;
    align-items: center;
    gap: 0.85rem;
  }

  .stat-icon-wrap {
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 0.6rem;
    background: rgba(200, 43, 52, 0.15);
    color: #E8454F;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(200, 43, 52, 0.3);
  }

  .stat-details {
    display: flex;
    flex-direction: column;
    text-align: start;
  }

  .stat-number {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--brand-accent, #C82B34);
    line-height: 1.2;
  }

  .stat-label {
    font-size: 0.82rem;
    color: #FAF8F5;
    opacity: 0.85;
    font-weight: 500;
  }

  .stat-separator {
    width: 1px;
    height: 2rem;
    background: rgba(213, 203, 193, 0.2);
  }

  /* ---------------- SECTION HEADINGS ---------------- */
  .section-heading-center {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.6rem;
    margin-bottom: 2.5rem;
  }

  .eyebrow-pill {
    display: inline-block;
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--brand-navy);
    background: rgba(var(--brand-navy-rgb), 0.08);
    border: 1.5px solid rgba(var(--brand-navy-rgb), 0.25);
    padding: 0.25rem 0.85rem;
    border-radius: 9999px;
  }

  :global(:root[data-theme='dark']) .eyebrow-pill {
    color: var(--brand-accent);
    background: rgba(var(--brand-accent-rgb), 0.15);
    border-color: rgba(var(--brand-accent-rgb), 0.35);
  }

  .section-title {
    font-size: clamp(1.6rem, 3.2vw, 2.25rem);
    font-weight: 900;
    color: var(--storm);
    margin: 0;
    line-height: 1.3;
  }

  .section-subtitle {
    color: var(--muted);
    font-size: 1rem;
    max-width: 580px;
    margin: 0;
    line-height: 1.6;
  }

  /* ---------------- STEPS SECTION ---------------- */
  .steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
  }

  .step-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 2rem 1.75rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    box-shadow: var(--shadow-sm);
    transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
  }

  .step-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    border-color: var(--deep-cyan);
  }

  .step-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .step-index {
    font-size: 1.85rem;
    font-weight: 900;
    color: var(--deep-cyan);
    opacity: 0.45;
  }

  .step-icon-wrap {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    background: var(--paper);
    color: var(--deep-cyan);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1.5px solid var(--line);
  }

  .featured-step-card {
    border-color: var(--brand-accent);
    background: linear-gradient(180deg, var(--card) 0%, rgba(var(--brand-accent-rgb), 0.04) 100%);
  }

  .highlight-index {
    color: var(--brand-accent);
    opacity: 0.95;
  }

  .highlight-icon {
    background: rgba(var(--brand-accent-rgb), 0.12);
    color: var(--brand-accent);
    border-color: rgba(var(--brand-accent-rgb), 0.4);
  }

  .step-title {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .step-desc {
    color: var(--muted);
    font-size: 0.92rem;
    line-height: 1.65;
    margin: 0;
  }

  /* ---------------- COURSES SECTION ---------------- */
  .section-header-flex {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
  }

  .link-view-all {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--deep-cyan);
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none;
    transition: transform 150ms ease, color 150ms ease;
  }

  :global(:root[data-theme='dark']) .link-view-all {
    color: var(--brand-accent);
  }

  .link-view-all:hover {
    transform: translateX(-3px);
  }

  .courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }

  .course-modern-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
  }

  .course-modern-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    border-color: var(--deep-cyan);
  }

  :global(:root[data-theme='dark']) .course-modern-card:hover {
    border-color: var(--brand-accent);
    box-shadow: 0 8px 30px rgba(var(--brand-accent-rgb), 0.15);
  }

  .card-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .course-slug-badge {
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--deep-cyan);
    background: var(--paper);
    padding: 0.25rem 0.65rem;
    border-radius: 0.4rem;
    border: 1px solid var(--line);
    text-transform: uppercase;
  }

  :global(:root[data-theme='dark']) .course-slug-badge {
    color: var(--deep-cyan);
    background: rgba(91, 122, 199, 0.15);
    border-color: rgba(91, 122, 199, 0.3);
  }

  .discount-badge {
    font-size: 0.72rem;
    font-weight: 800;
    color: #92400e;
    background: #fef3c7;
    padding: 0.2rem 0.55rem;
    border-radius: 0.35rem;
  }

  .card-titles {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
  }

  .card-title-ar {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .card-title-en {
    font-size: 0.85rem;
    color: var(--muted);
    margin: 0;
  }

  .card-bottom-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1.25rem;
    border-top: 2px solid var(--line);
  }

  .price-stack {
    display: flex;
    flex-direction: column;
  }

  .price-caption {
    font-size: 0.75rem;
    color: var(--muted);
  }

  .price-value-row {
    display: flex;
    align-items: baseline;
    gap: 0.45rem;
  }

  .strikethrough-price {
    font-size: 0.82rem;
    color: var(--muted);
    text-decoration: line-through;
  }

  .final-price {
    font-size: 1.2rem;
    font-weight: 900;
    color: var(--storm);
  }

  .btn-card-explore {
    background: var(--brand-navy);
    color: #FAF8F5;
    font-weight: 800;
    font-size: 0.88rem;
    padding: 0.55rem 1.15rem;
    border-radius: 0.5rem;
    text-decoration: none;
    border: 2px solid var(--brand-navy);
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: opacity 150ms ease, transform 120ms ease, background-color 150ms ease, border-color 150ms ease;
  }

  .btn-card-explore:hover {
    opacity: 0.95;
    transform: translateY(-1px);
    background-color: var(--brand-accent);
    border-color: var(--brand-accent);
    color: #ffffff;
  }

  /* ---------------- FEATURES SECTION ---------------- */
  .features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
  }

  .feature-item {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 2rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    box-shadow: var(--shadow-sm);
    transition: transform 160ms ease, border-color 160ms ease;
  }

  .feature-item:hover {
    transform: translateY(-3px);
    border-color: var(--deep-cyan);
  }

  .feature-icon-box {
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 0.75rem;
    background: rgba(var(--brand-navy-rgb), 0.08);
    color: var(--brand-navy);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.35rem;
  }

  :global([data-theme='dark']) .feature-icon-box {
    background: rgba(91, 122, 199, 0.15);
    color: var(--deep-cyan);
  }

  .feature-item h3 {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .feature-item p {
    color: var(--muted);
    font-size: 0.9rem;
    line-height: 1.65;
    margin: 0;
  }

  /* ---------------- FAQ ACCORDION ---------------- */
  .faq-accordion {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    max-width: 820px;
    margin-inline: auto;
    width: 100%;
  }

  .faq-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 0.85rem;
    overflow: hidden;
    transition: border-color 150ms ease;
  }

  .faq-card:hover {
    border-color: var(--deep-cyan);
  }

  .faq-card-open {
    border-color: var(--deep-cyan);
  }

  :global(:root[data-theme='dark']) .faq-card-open {
    border-color: var(--brand-accent);
  }

  .faq-trigger {
    width: 100%;
    padding: 1.25rem 1.5rem;
    background: none;
    border: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    cursor: pointer;
    text-align: right;
    font-family: inherit;
  }

  .faq-question {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--storm);
  }

  .faq-icon-arrow {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--deep-cyan);
    flex-shrink: 0;
  }

  :global(:root[data-theme='dark']) .faq-icon-arrow {
    color: var(--brand-accent);
  }

  .faq-content {
    padding: 0 1.5rem 1.25rem;
    border-top: 2px solid var(--line);
  }

  .faq-content p {
    color: var(--muted);
    line-height: 1.75;
    margin: 0.75rem 0 0;
    font-size: 0.92rem;
  }

  /* ---------------- CTA BANNER ---------------- */
  .cta-banner {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #1A1918 0%, #1E2B4D 100%);
    color: white;
    border-radius: 1.75rem;
    border: 2px solid var(--line);
    padding: clamp(2.5rem, 5vw, 4rem) 2rem;
    text-align: center;
    box-shadow: 0 20px 40px -10px rgba(26, 25, 24, 0.4);
  }

  :global(:root[data-theme='dark']) .cta-banner {
    background: linear-gradient(135deg, #121211 0%, #17223D 100%);
    border-color: rgba(91, 122, 199, 0.3);
  }

  .cta-banner-bg {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(213, 203, 193, 0.18) 1px, transparent 1px);
    background-size: 24px 24px;
    opacity: 0.4;
  }

  .cta-content {
    position: relative;
    z-index: 2;
    max-width: 650px;
    margin-inline: auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }

  .cta-badge {
    font-size: 0.78rem;
    font-weight: 800;
    background: rgba(var(--brand-accent-rgb), 0.15);
    color: #FF7B82;
    padding: 0.25rem 0.85rem;
    border-radius: 9999px;
    border: 1px solid rgba(var(--brand-accent-rgb), 0.35);
  }

  .cta-banner h2 {
    color: white;
    font-size: clamp(1.8rem, 3.5vw, 2.4rem);
    font-weight: 900;
    margin: 0;
  }

  .cta-banner p {
    color: #D5CBC1;
    font-size: 1.05rem;
    line-height: 1.75;
    margin: 0;
  }

  .cta-actions {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 0.75rem;
  }

  .btn-cta-primary {
    background: var(--brand-accent);
    color: #ffffff;
    font-weight: 800;
    padding: 0.75rem 1.65rem;
    border-radius: 0.6rem;
    text-decoration: none;
    border: 2px solid var(--brand-accent);
    box-shadow: 0 0 20px rgba(200, 43, 52, 0.35);
    transition: transform 150ms ease, box-shadow 150ms ease;
  }

  .btn-cta-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 28px rgba(200, 43, 52, 0.55);
  }

  .btn-cta-secondary {
    background: transparent;
    color: white;
    font-weight: 700;
    padding: 0.75rem 1.5rem;
    border-radius: 0.6rem;
    text-decoration: none;
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: background 150ms ease, border-color 150ms ease;
  }

  .btn-cta-secondary:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: white;
  }

  .notice-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1rem;
    padding: 2.5rem;
    text-align: center;
    color: var(--muted);
  }

  .notice-card.error {
    border-color: #f87171;
    color: #991b1b;
  }

  /* ---------------- RESPONSIVENESS ---------------- */
  @media (max-width: 860px) {
    .steps-grid {
      grid-template-columns: 1fr;
    }

    .stats-glass-bar {
      flex-direction: column;
      align-items: stretch;
      gap: 1.25rem;
      padding: 1.25rem 1.5rem;
    }

    .stat-pill {
      justify-content: flex-start;
    }

    .stat-separator {
      display: none;
    }

    .section-header-flex {
      flex-direction: column;
      align-items: flex-start;
    }

    .courses-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 480px) {
    .landing-container {
      gap: 3.5rem;
      padding: 3rem 0.75rem 3.5rem;
    }

    .hero-section {
      border-radius: 0;
      border: none;
      padding: 3.5rem 1.25rem;
    }

    .hero-actions {
      flex-direction: column;
      width: 100%;
    }

    .btn-hero-primary, .btn-hero-secondary {
      width: 100%;
      justify-content: center;
    }

    .cta-actions {
      flex-direction: column;
      width: 100%;
    }

    .btn-cta-primary, .btn-cta-secondary {
      width: 100%;
      text-align: center;
    }
  }
</style>
