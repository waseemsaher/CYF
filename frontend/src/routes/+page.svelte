<script lang="ts">
  import type { PageData } from './$types';

  let { data }: { data: PageData } = $props();

  const formatPrice = (cents: number) => `${(cents / 100).toLocaleString('ar-EG')} ج.م`;

  let openFaqIndex = $state<number | null>(0);

  function toggleFaq(index: number) {
    openFaqIndex = openFaqIndex === index ? null : index;
  }

  const defaultFaqs = [
    {
      q: 'كيف يمكنني الانضمام لمجموعة التليجرام الخاصة بالمقرر؟',
      a: 'بعد اعتماد إيصال السداد الخاص بك، ادخل إلى صفحة المقرر واضغط على "ربط حساب تليجرام" لتوليد رابط الدخول الخاص بك. عند إرسال طلب الانضمام، يقوم بوت المنصة بالتحقق من اشتراكك وقبولك فوراً وبشكل آلي.'
    },
    {
      q: 'ما هي طرق الدفع المتاحة للاشتراك؟',
      a: 'نوفر الدفع اليدوي السهل عبر محفظة فودافون كاش (Vodafone Cash)، أو إنستاباي (InstaPay)، أو المحافظ الإلكترونية الأخرى. بعد التحويل، ارفع صورة إيصال التحويل في صفحة إتمام الطلب.'
    },
    {
      q: 'كم يستغرق وقت مراجعة وتأكيد الاشتراك؟',
      a: 'تتم مراجعة إيصالات الدفع بواسطة فريق الإدارة بانتظام وفي أسرع وقت ممكن (عادة خلال دقائق إلى ساعات معدودة). ستصلك رسالة تأكيد عبر البريد الإلكتروني فور الاعتماد.'
    },
    {
      q: 'ما مدة صلاحية اشتراكي في المادة؟',
      a: 'يستمر اشتراكك فعالاً حتى نهاية الفصل الدراسي الرسمي للمقرر، مع فترة سماح إضافية لمراجعة المحتوى والاختبارات حتى انتهاء موسم الامتحانات.'
    }
  ];
</script>

<svelte:head>
  <title>منصة دورات FCAI | كلية الحاسبات والذكاء الاصطناعي — جامعة الأزهر</title>
  <meta
    name="description"
    content="المنصة الأكاديمية الأولى لشروحات ومقررات واختبارات طلاب كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر."
  />
</svelte:head>

<div class="landing-container">
  <!-- Hero Section -->
  <section class="hero-section">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="hero-content">
      <div class="badge">
        <span class="badge-dot"></span>
        <span>منصة طلاب كلية الحاسبات والذكاء الاصطناعي — جامعة الأزهر</span>
      </div>

      <h1 class="hero-title">
        شروحات مركزة ومباشرة، <br />
        <span class="highlight">خطوتك الثابتة نحو التفوق الأكاديمي.</span>
      </h1>

      <p class="hero-description">
        {data.whyUs?.ar ??
          'محتوى دراسي منظم يغطي مناهج الكلية بدقة، مجموعات تليجرام مقفولة للمناقشة المستمرة، واختبارات تفاعلية تقيس استيعابك للمقرر أولاً بأول.'}
      </p>

      <div class="hero-actions">
        <a href="/courses" class="btn-primary">
          <span>استكشف المقررات المتاحة</span>
          <span aria-hidden="true" class="arrow">←</span>
        </a>
        <a href="/login" class="btn-secondary">
          <span>تسجيل الدخول</span>
        </a>
      </div>

      <div class="hero-stats">
        <div class="stat-item">
          <strong>+5</strong>
          <span>مقررات أساسية معتمدة</span>
        </div>
        <div class="stat-divider" aria-hidden="true"></div>
        <div class="stat-item">
          <strong>100%</strong>
          <span>مجموعات تليجرام موثقة</span>
        </div>
        <div class="stat-divider" aria-hidden="true"></div>
        <div class="stat-item">
          <strong>فوري</strong>
          <span>قبول آلي عبر البوت</span>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section class="steps-section" aria-labelledby="steps-title">
    <div class="section-header">
      <span class="eyebrow">خطوات بسيطة</span>
      <h2 id="steps-title">كيف تبدأ رحلتك التعليمية؟</h2>
      <p class="section-subtitle">ثلاث خطوات فقط تفصلك عن الوصول لشروحات ومجموعات المقرر.</p>
    </div>

    <div class="steps-grid">
      <div class="step-card">
        <div class="step-number">01</div>
        <h3>اختر مقررك الدراسي</h3>
        <p>تصفح قائمة المقررات المتاحة لفرقتك وقسمك (علوم حاسب، نظم، ذكاء اصطناعي)، واطلع على تفاصيل المحتوى.</p>
      </div>

      <div class="step-card">
        <div class="step-number">02</div>
        <h3>سدد الرسوم وارفع الإيصال</h3>
        <p>قم بالتحويل عبر فودافون كاش أو إنستاباي لحساب المنصة الموضح، وارفع لقطة شاشة للإيصال في صفحة السداد.</p>
      </div>

      <div class="step-card highlight-card">
        <div class="step-number">03</div>
        <h3>انضم للتليجرام وابدأ المذاكرة</h3>
        <p>فور اعتماد الإيصال، يُفتح لك محتوى المادة فوراً ويتم قبول انضمامك تلقائياً لمجموعة التليجرام المقفولة.</p>
      </div>
    </div>
  </section>

  <!-- Featured Courses Section -->
  <section class="courses-section" aria-labelledby="courses-title">
    <div class="section-header-row">
      <div>
        <span class="eyebrow">المحتوى الدراسي</span>
        <h2 id="courses-title">المقررات المتاحة الآن</h2>
      </div>
      <a href="/courses" class="link-all">
        <span>عرض جميع المقررات</span>
        <span aria-hidden="true">←</span>
      </a>
    </div>

    {#if data.error}
      <div class="notice" role="status">
        <p>{data.error}</p>
      </div>
    {:else if data.courses.length === 0}
      <div class="notice">
        <p>ستظهر المقررات الدراسية المتاحة هنا فور إطلاقها.</p>
      </div>
    {:else}
      <div class="courses-grid">
        {#each data.courses as course}
          <article class="course-card">
            <div class="course-header">
              <span class="course-code">{course.slug}</span>
              {#if course.discount_cents > 0}
                <span class="discount-tag">خصم متاح</span>
              {/if}
            </div>

            <div class="course-body">
              <h3 class="course-title-ar">{course.title.ar}</h3>
              <p class="course-title-en">{course.title.en}</p>
            </div>

            <div class="course-footer">
              <div class="price-box">
                {#if course.discount_cents > 0}
                  <span class="original-price">{formatPrice(course.list_price_cents)}</span>
                {/if}
                <strong class="current-price">
                  {course.amount_due_cents === 0 ? 'مجانًا' : formatPrice(course.amount_due_cents)}
                </strong>
              </div>

              <a href={`/courses/${course.slug}`} class="btn-course-details">
                <span>التفاصيل</span>
                <span aria-hidden="true">←</span>
              </a>
            </div>
          </article>
        {/each}
      </div>
    {/if}
  </section>

  <!-- Why Us Section -->
  <section class="why-us-section" aria-labelledby="why-title">
    <div class="section-header">
      <span class="eyebrow">مميزات المنصة</span>
      <h2 id="why-title">لماذا يختار طلاب الكلية منصتنا؟</h2>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">🎯</div>
        <h3>مطابقة دقيقة للمنهج</h3>
        <p>شروحات تركز تحديداً على توصيف المقررات بجامعة الأزهر ومسائل الامتحانات السابقة دون إطالة غير مفيدة.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon">🔒</div>
        <h3>بيئة تليجرام مقفولة وموثقة</h3>
        <p>مجموعات نقاش مخصصة للطلاب المشتركين فقط، يتم إدارتها آلياً ببوت ذكي يضمن خصوصية ونظام المجموعة.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h3>اختبارات تفاعلية مستمرة</h3>
        <p>تدرب بعد كل درس عبر بنك أسئلة واختبارات مؤقتة تحاكي طبيعة الامتحانات وتصحح إجاباتك فوراً مع التفسير.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon">📱</div>
        <h3>تجربة سهلة على الهاتف</h3>
        <p>واجهة خفيفة وسريعة مصممة لتعمل بسلاسة على الهواتف وشبكات المحمول لسهولة الوصول في أي وقت.</p>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="faq-section" aria-labelledby="faq-title">
    <div class="section-header">
      <span class="eyebrow">توضيحات وإجابات</span>
      <h2 id="faq-title">الأسئلة الشائعة</h2>
      <p class="section-subtitle">كل ما تود معرفته حول الاشتراك وتفعيل المجموعات واستخدام المنصة.</p>
    </div>

    <div class="faq-accordion">
      {#each defaultFaqs as faq, index}
        <div class="faq-item" class:open={openFaqIndex === index}>
          <button
            type="button"
            class="faq-question"
            aria-expanded={openFaqIndex === index}
            onclick={() => toggleFaq(index)}
          >
            <span>{faq.q}</span>
            <span class="faq-icon" aria-hidden="true">{openFaqIndex === index ? '−' : '+'}</span>
          </button>
          {#if openFaqIndex === index}
            <div class="faq-answer">
              <p>{faq.a}</p>
            </div>
          {/if}
        </div>
      {/each}
    </div>
  </section>

  <!-- CTA Banner Section -->
  <section class="cta-banner">
    <div class="cta-inner">
      <h2>جاهز لبدء الفصل الدراسي بثقة؟</h2>
      <p>انضم الآن إلى زملائك وابدأ الاستعداد للمقررات مع شروحات وافية واختبارات متجددة.</p>
      <div class="cta-buttons">
        <a href="/courses" class="btn-primary">تصفح المقررات الدراسية</a>
        <a href="/register" class="btn-secondary-light">إنشاء حساب طالب جديد</a>
      </div>
    </div>
  </section>
</div>

<style>
  .landing-container {
    display: flex;
    flex-direction: column;
    gap: 5rem;
    padding-bottom: 5rem;
  }

  /* Hero Section */
  .hero-section {
    position: relative;
    background: var(--storm);
    color: white;
    border-radius: 1.25rem;
    padding: clamp(3rem, 6vw, 5.5rem) clamp(1.5rem, 5vw, 4rem);
    overflow: hidden;
    margin-top: 1.5rem;
    box-shadow: 0 20px 40px -15px rgba(15, 40, 47, 0.4);
  }

  .hero-glow {
    position: absolute;
    top: -10rem;
    inset-inline-end: -8rem;
    width: 26rem;
    height: 26rem;
    background: radial-gradient(circle, rgba(2, 239, 240, 0.25) 0%, transparent 70%);
    pointer-events: none;
  }

  .hero-content {
    position: relative;
    z-index: 1;
    max-width: 820px;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    background: rgba(2, 239, 240, 0.1);
    border: 1px solid rgba(2, 239, 240, 0.3);
    color: var(--cyan);
    padding: 0.4rem 1rem;
    border-radius: 9999px;
    font-size: 0.82rem;
    font-weight: 600;
    margin-bottom: 1.75rem;
  }

  .badge-dot {
    width: 0.5rem;
    height: 0.5rem;
    background: var(--cyan);
    border-radius: 50%;
    box-shadow: 0 0 8px var(--cyan);
  }

  .hero-title {
    font-size: clamp(2.2rem, 5.5vw, 3.8rem);
    font-weight: 900;
    line-height: 1.2;
    margin: 0 0 1.5rem;
    letter-spacing: -0.02em;
  }

  .highlight {
    color: var(--cyan);
  }

  .hero-description {
    font-size: clamp(1.05rem, 2vw, 1.2rem);
    line-height: 1.85;
    color: #cadbd8;
    margin: 0 0 2.25rem;
    max-width: 680px;
  }

  .hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 3rem;
  }

  .btn-primary {
    background: var(--cyan);
    color: var(--storm);
    font-weight: 800;
    padding: 0.85rem 1.75rem;
    border-radius: 0.5rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    transition: transform 160ms ease, filter 160ms ease;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    filter: brightness(1.05);
  }

  .btn-secondary {
    background: rgba(255, 255, 255, 0.08);
    color: white;
    font-weight: 700;
    padding: 0.85rem 1.75rem;
    border-radius: 0.5rem;
    text-decoration: none;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: background 160ms ease;
  }

  .btn-secondary:hover {
    background: rgba(255, 255, 255, 0.16);
  }

  .btn-secondary-light {
    background: white;
    color: var(--storm);
    font-weight: 800;
    padding: 0.85rem 1.75rem;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: background 160ms ease;
  }

  .btn-secondary-light:hover {
    background: #eef5f4;
  }

  .hero-stats {
    display: flex;
    align-items: center;
    gap: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
    padding-top: 1.75rem;
  }

  .stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
  }

  .stat-item strong {
    font-size: 1.6rem;
    font-weight: 900;
    color: var(--cyan);
  }

  .stat-item span {
    font-size: 0.82rem;
    color: #a7c0bc;
  }

  .stat-divider {
    width: 1px;
    height: 2.2rem;
    background: rgba(255, 255, 255, 0.15);
  }

  /* Section Headers */
  .section-header {
    text-align: center;
    max-width: 650px;
    margin: 0 auto 3rem;
  }

  .section-header-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 2.5rem;
    gap: 1.5rem;
  }

  .eyebrow {
    display: block;
    color: var(--deep-cyan);
    font-weight: 800;
    font-size: 0.85rem;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
  }

  h2 {
    font-size: clamp(1.8rem, 3.5vw, 2.5rem);
    font-weight: 800;
    color: var(--storm);
    margin: 0 0 0.5rem;
    line-height: 1.25;
  }

  .section-subtitle {
    color: var(--muted);
    font-size: 1.05rem;
    margin: 0;
  }

  .link-all {
    color: var(--deep-cyan);
    text-decoration: none;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.95rem;
  }

  .link-all:hover {
    text-decoration: underline;
  }

  /* Steps Section */
  .steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
  }

  .step-card {
    background: white;
    border: 1px solid var(--line);
    border-radius: 1rem;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    position: relative;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.03);
    transition: transform 160ms ease, box-shadow 160ms ease;
  }

  .step-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -8px rgba(15, 40, 47, 0.08);
  }

  .step-number {
    font-size: 2.2rem;
    font-weight: 900;
    color: var(--deep-cyan);
    opacity: 0.7;
    line-height: 1;
  }

  .step-card h3 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .step-card p {
    color: var(--muted);
    line-height: 1.7;
    margin: 0;
    font-size: 0.95rem;
  }

  .highlight-card {
    border-color: rgba(2, 239, 240, 0.8);
    background: linear-gradient(180deg, #ffffff 0%, #f1faf9 100%);
  }

  /* Courses Grid */
  .courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
  }

  .course-card {
    background: white;
    border: 1px solid var(--line);
    border-radius: 1rem;
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 240px;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.04);
    transition: transform 160ms ease, border-color 160ms ease;
  }

  .course-card:hover {
    transform: translateY(-4px);
    border-color: var(--deep-cyan);
  }

  .course-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
  }

  .course-code {
    font-size: 0.8rem;
    font-weight: 800;
    color: var(--deep-cyan);
    background: #eef7f6;
    padding: 0.25rem 0.6rem;
    border-radius: 0.35rem;
  }

  .discount-tag {
    font-size: 0.75rem;
    font-weight: 800;
    color: #c05621;
    background: #feebc8;
    padding: 0.2rem 0.5rem;
    border-radius: 0.35rem;
  }

  .course-title-ar {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0 0 0.4rem;
  }

  .course-title-en {
    font-size: 0.88rem;
    color: var(--muted);
    margin: 0;
  }

  .course-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid var(--line);
    padding-top: 1.25rem;
    margin-top: 1.5rem;
  }

  .price-box {
    display: flex;
    flex-direction: column;
  }

  .original-price {
    font-size: 0.78rem;
    color: var(--muted);
    text-decoration: line-through;
  }

  .current-price {
    font-size: 1.25rem;
    font-weight: 900;
    color: var(--storm);
  }

  .btn-course-details {
    background: #eef7f6;
    color: var(--deep-cyan);
    font-weight: 800;
    padding: 0.5rem 1rem;
    border-radius: 0.4rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.88rem;
    transition: background 160ms ease, color 160ms ease;
  }

  .btn-course-details:hover {
    background: var(--deep-cyan);
    color: white;
  }

  /* Features Grid */
  .features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
  }

  .feature-card {
    background: white;
    border: 1px solid var(--line);
    border-radius: 1rem;
    padding: 1.75rem;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.03);
  }

  .feature-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
  }

  .feature-card h3 {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0 0 0.6rem;
  }

  .feature-card p {
    color: var(--muted);
    line-height: 1.7;
    margin: 0;
    font-size: 0.92rem;
  }

  /* FAQ Accordion */
  .faq-accordion {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
  }

  .faq-item {
    background: white;
    border: 1px solid var(--line);
    border-radius: 0.75rem;
    overflow: hidden;
    transition: border-color 160ms ease;
  }

  .faq-item.open {
    border-color: var(--deep-cyan);
  }

  .faq-question {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    background: none;
    border: none;
    text-align: start;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--storm);
    cursor: pointer;
    font-family: inherit;
  }

  .faq-icon {
    font-size: 1.4rem;
    color: var(--deep-cyan);
    font-weight: 700;
    line-height: 1;
  }

  .faq-answer {
    padding: 0 1.5rem 1.5rem;
    border-top: 1px solid #f0f5f4;
  }

  .faq-answer p {
    color: var(--muted);
    line-height: 1.8;
    margin: 0.75rem 0 0;
    font-size: 0.95rem;
  }

  /* CTA Banner */
  .cta-banner {
    background: linear-gradient(135deg, var(--storm) 0%, #153c47 100%);
    color: white;
    border-radius: 1.25rem;
    padding: clamp(2.5rem, 5vw, 4rem) 2rem;
    text-align: center;
    box-shadow: 0 16px 32px -12px rgba(15, 40, 47, 0.35);
  }

  .cta-inner {
    max-width: 650px;
    margin: 0 auto;
  }

  .cta-banner h2 {
    color: white;
    font-size: clamp(1.8rem, 3.5vw, 2.4rem);
    margin: 0 0 1rem;
  }

  .cta-banner p {
    color: #cadbd8;
    font-size: 1.05rem;
    line-height: 1.75;
    margin: 0 0 2rem;
  }

  .cta-buttons {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .notice {
    background: white;
    border: 1px solid var(--line);
    border-radius: 0.75rem;
    padding: 2rem;
    text-align: center;
    color: var(--muted);
  }

  @media (max-width: 860px) {
    .steps-grid {
      grid-template-columns: 1fr;
    }

    .hero-stats {
      flex-wrap: wrap;
      gap: 1.25rem;
    }

    .stat-divider {
      display: none;
    }
  }
</style>
