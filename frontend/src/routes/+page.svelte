<script lang="ts">
  import type { PageData } from './$types';

  let { data }: { data: PageData } = $props();
  const formatPrice = (cents: number) => `${(cents / 100).toLocaleString('ar-EG')} جنيه`;
</script>

<svelte:head>
  <title>منصة FCAI | تعلم بوضوح</title>
  <meta name="description" content="دورات مركزة لطلاب كلية الحاسبات والذكاء الاصطناعي" />
</svelte:head>

<div class="landing-page">
  <section class="hero">
    <div class="hero-copy">
      <p class="eyebrow">منصة طلاب FCAI</p>
      <h1>تعلم ما تحتاجه، في الوقت الذي يناسبك.</h1>
      <p class="hero-text">محتوى مرتب، شرح مباشر، ومسارات مصممة لتساعدك على فهم مقرراتك وبناء مهاراتك بثبات.</p>
      <a class="primary-action" href="/courses">استكشف الدورات <span aria-hidden="true">←</span></a>
    </div>
    <div class="hero-signal" aria-hidden="true"><span class="signal-label">FOCUS / BUILD / PASS</span><strong>01</strong><span>مسارك يبدأ من هنا</span></div>
  </section>

  <section class="principles" aria-labelledby="principles-title">
    <div><p class="eyebrow">لماذا FCAI؟</p><h2 id="principles-title">أقل تشتتًا.<br />أكثر تقدمًا.</h2></div>
    <div class="principle-list">
      <article><span>01</span><h3>محتوى واضح</h3><p>موضوعات مرتبة تضع المهم أمامك دون ضوضاء.</p></article>
      <article><span>02</span><h3>إيقاعك الخاص</h3><p>راجع الدروس والاختبارات عندما يكون تركيزك في أفضل حالاته.</p></article>
      <article><span>03</span><h3>موجه للمقرر</h3><p>مسارات قريبة من احتياجات طلاب الكلية الفعلية.</p></article>
    </div>
  </section>

  <section class="featured" aria-labelledby="featured-title">
    <div class="section-heading"><div><p class="eyebrow">ابدأ الآن</p><h2 id="featured-title">الدورات المتاحة</h2></div><a href="/courses">عرض الكل <span aria-hidden="true">←</span></a></div>
    {#if data.error}
      <p class="notice" role="status">{data.error}</p>
    {:else if data.courses.length === 0}
      <p class="notice">ستظهر الدورات المتاحة هنا قريبًا.</p>
    {:else}
      <div class="course-row">
        {#each data.courses as course}
          <a class="course-card" href={`/courses/${course.slug}`}>
            <span class="course-index">{String(course.id).padStart(2, '0')}</span>
            <div><p>{course.title.en}</p><h3>{course.title.ar}</h3></div>
            <div class="course-price">{#if course.discount_cents > 0}<span>{formatPrice(course.list_price_cents)}</span>{/if}<strong>{formatPrice(course.amount_due_cents)}</strong></div>
          </a>
        {/each}
      </div>
    {/if}
  </section>
</div>

<style>
  .landing-page { padding: 2rem 0; }
  .hero, .principles, .featured { margin-inline: auto; max-width: 1180px; }
  .section-heading a { color: var(--deep-cyan); font-weight: 800; text-decoration: none; }
  .hero { background: var(--storm); border-radius: 0.75rem; color: white; display: grid; gap: 2rem; grid-template-columns: minmax(0, 1.5fr) minmax(220px, 0.8fr); margin-bottom: 6rem; overflow: hidden; padding: clamp(2rem, 7vw, 6rem); position: relative; }
  .hero::after { background: var(--cyan); content: ''; height: 18rem; opacity: 0.9; position: absolute; right: -5rem; top: -8rem; transform: rotate(24deg); width: 14rem; }
  .hero-copy, .hero-signal { position: relative; z-index: 1; }
  .eyebrow { color: var(--deep-cyan); font-size: 0.8rem; font-weight: 900; letter-spacing: 0.06em; margin: 0 0 0.8rem; }
  .hero .eyebrow { color: var(--cyan); }
  h1 { font-size: clamp(2.5rem, 7vw, 6.3rem); line-height: 1.02; margin: 0; max-width: 780px; }
  .hero-text { color: #d9eeee; font-size: 1.1rem; line-height: 1.9; margin: 1.5rem 0 2rem; max-width: 620px; }
  .primary-action { background: var(--cyan); color: var(--storm); display: inline-flex; font-weight: 900; gap: 0.75rem; padding: 0.85rem 1.2rem; text-decoration: none; border-radius: 0.25rem; }
  .hero-signal { align-self: end; border-top: 1px solid rgba(255,255,255,0.45); display: grid; gap: 0.7rem; padding-top: 1rem; }
  .signal-label { color: var(--cyan); font-size: 0.7rem; letter-spacing: 0.12em; }
  .hero-signal strong { font-size: 5rem; line-height: 0.8; }
  .principles { display: grid; gap: 3rem; grid-template-columns: 0.7fr 1.3fr; padding: 0 1.25rem 6rem; }
  h2 { font-size: clamp(2rem, 4vw, 3.6rem); line-height: 1.1; margin: 0; }
  .principle-list { border-top: 1px solid var(--line); }
  article { border-bottom: 1px solid var(--line); display: grid; gap: 1rem; grid-template-columns: 2.5rem 1fr 1.2fr; padding: 1.2rem 0; }
  article span, .course-index { color: var(--deep-cyan); font-weight: 900; }
  h3 { font-size: 1.15rem; margin: 0; }
  article p { color: var(--muted); line-height: 1.7; margin: 0; }
  .featured { padding: 0 1.25rem 4rem; }
  .section-heading { align-items: end; display: flex; justify-content: space-between; margin-bottom: 1.5rem; }
  .course-row { display: grid; gap: 0.75rem; grid-template-columns: repeat(3, 1fr); }
  .course-card { background: white; border: 1px solid var(--line); color: var(--ink); display: grid; gap: 1rem; grid-template-rows: auto 1fr auto; min-height: 220px; padding: 1.25rem; text-decoration: none; border-radius: 0.5rem; transition: transform 160ms ease, border-color 160ms ease; }
  .course-card:hover { border-color: var(--deep-cyan); transform: translateY(-4px); }
  .course-card p { color: var(--muted); font-size: 0.78rem; margin: 0 0 0.5rem; }
  .course-card h3 { font-size: 1.45rem; }
  .course-price strong, .course-price span { display: block; }
  .course-price span { color: var(--muted); font-size: 0.75rem; text-decoration: line-through; }
  .notice { background: white; border: 1px solid var(--line); padding: 1rem; border-radius: 0.375rem; }
  @media (max-width: 760px) { .hero { grid-template-columns: 1fr; margin-inline: 1rem; margin-bottom: 4rem; } .hero::after { right: -8rem; } .principles { grid-template-columns: 1fr; padding-bottom: 4rem; } article { grid-template-columns: 2rem 1fr; } article p { grid-column: 2; } .course-row { grid-template-columns: 1fr; } }
</style>
