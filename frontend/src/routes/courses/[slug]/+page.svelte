<script lang="ts">
  import type { PageData } from './$types';

  let { data }: { data: PageData } = $props();
  const formatPrice = (cents: number) => `${(cents / 100).toLocaleString('ar-EG')} جنيه`;
</script>

<svelte:head>
  <title>{data.course.title.ar} | منصة FCAI</title>
  <meta name="description" content={data.course.description.ar} />
</svelte:head>

<main class="detail-shell">
  <header class="detail-header">
    <a class="brand" href="/">FCAI <span>COURSES</span></a>
    <a class="back-link" href="/courses">العودة للدورات <span aria-hidden="true">→</span></a>
  </header>

  <section class="course-hero">
    <div class="hero-copy">
      <p class="kicker">دورة متاحة للتسجيل</p>
      <h1>{data.course.title.ar}</h1>
      <p class="description">{data.course.description.ar}</p>
    </div>

    <aside class="enrollment-panel" aria-label="تفاصيل السعر والتسجيل">
      <p>السعر الحالي</p>
      {#if data.course.discount_cents > 0}
        <span class="old-price">{formatPrice(data.course.list_price_cents)}</span>
      {/if}
      <strong>{formatPrice(data.course.amount_due_cents)}</strong>
      {#if data.course.amount_due_cents === 0}
        <span class="free-label">مجانية</span>
        <a class="enroll-btn free" href={`/courses/${data.course.slug}/checkout`}>سجّل فورًا — مجانًا</a>
      {:else}
        <a class="enroll-btn" href={`/courses/${data.course.slug}/checkout`}>سجّل الآن <span aria-hidden="true">←</span></a>
      {/if}
    </aside>
  </section>

  <section class="outline">
    <p class="kicker">نظرة عامة</p>
    <h2>ابدأ بخطوة واضحة</h2>
    <p>ستجد داخل الدورة محتوى مرتبًا وروابط المحاضرات والاختبارات الخاصة بالمقرر.</p>
  </section>
</main>

<style>
  :global(body) { margin: 0; background: #f3f7f6; color: #0f282f; font-family: 'IBM Plex Sans Arabic', Tahoma, sans-serif; }
  :global(*) { box-sizing: border-box; }
  .detail-shell { margin: 0 auto; max-width: 1180px; padding: 1.25rem 1.25rem 4rem; }
  .detail-header { align-items: center; display: flex; justify-content: space-between; padding: 0.5rem 0 4rem; }
  .brand { color: #0f282f; font-size: 1.05rem; font-weight: 800; letter-spacing: 0.08em; text-decoration: none; }
  .brand span { color: #17777a; font-size: 0.68rem; margin-inline-start: 0.35rem; }
  .back-link { color: #17777a; font-weight: 800; text-decoration: none; }
  .course-hero { align-items: end; background: #0f282f; border-radius: 0.75rem; color: white; display: grid; gap: 2rem; grid-template-columns: 1fr minmax(260px, 340px); padding: clamp(1.5rem, 5vw, 4rem); }
  .kicker { color: #02eff0; font-size: 0.8rem; font-weight: 800; letter-spacing: 0.04em; margin: 0 0 0.75rem; }
  h1 { font-size: clamp(2.3rem, 7vw, 5.5rem); line-height: 1.05; margin: 0; }
  .description { color: #d7eeee; font-size: 1.08rem; line-height: 1.9; margin: 1.25rem 0 0; max-width: 650px; }
  .enrollment-panel { background: white; border-radius: 0.5rem; color: #0f282f; padding: 1.25rem; }
  .enrollment-panel p { color: #49636a; margin: 0 0 0.75rem; }
  .enrollment-panel strong { display: block; font-size: 2rem; }
  .old-price { color: #799095; display: block; text-decoration: line-through; }
  .free-label { color: #17777a; display: block; font-weight: 800; margin-top: 0.35rem; }
  .enroll-btn { align-items: center; background: #02eff0; border: 0; border-radius: 0.4rem; color: #0f282f; display: flex; font: inherit; font-weight: 900; gap: 0.5rem; justify-content: center; margin-top: 1.25rem; min-height: 2.8rem; text-decoration: none; transition: opacity 150ms; width: 100%; }
  .enroll-btn:hover { opacity: 0.85; }
  .enroll-btn.free { background: #17777a; color: white; }
  .outline { background: white; border-radius: 0.5rem; margin-top: 1rem; padding: 2rem; }
  .outline .kicker { color: #17777a; }
  .outline h2 { font-size: 1.8rem; margin: 0 0 0.5rem; }
  .outline p:last-child { color: #49636a; line-height: 1.8; margin: 0; }
  @media (max-width: 760px) { .detail-shell { padding-inline: 1rem; } .detail-header { padding-bottom: 2rem; } .course-hero { grid-template-columns: 1fr; } }
</style>