<script lang="ts">
  import type { PageData } from './$types';

  let { data }: { data: PageData } = $props();

  const formatPrice = (cents: number) => `${(cents / 100).toLocaleString('ar-EG')} جنيه`;
  const pageHref = (page: number) => {
    const params = new URLSearchParams();
    if (data.selectedAcademicYear) params.set('academic_year_id', data.selectedAcademicYear);
    if (data.selectedDepartment) params.set('department_id', data.selectedDepartment);
    params.set('page', String(page));
    return `/courses?${params.toString()}`;
  };
</script>

<svelte:head>
  <title>الدورات | منصة FCAI</title>
  <meta name="description" content="تصفح دورات كلية الحاسبات والذكاء الاصطناعي" />
</svelte:head>

<main class="catalog-shell">
  <header class="catalog-header">
    <a class="brand" href="/">FCAI <span>COURSES</span></a>
    <nav aria-label="التنقل الرئيسي">
      <a href="/courses" aria-current="page">الدورات</a>
    </nav>
  </header>

  <section class="intro">
    <p class="kicker">دورات كلية الحاسبات والذكاء الاصطناعي</p>
    <h1>اختَر مسارك الدراسي</h1>
    <p class="lede">دورات مركزة تساعدك على بناء أساس قوي ومراجعة أهم موضوعات المقرر.</p>
  </section>

  <form class="filters" method="GET" aria-label="تصفية الدورات">
    <label>
      <span>السنة الدراسية</span>
      <select name="academic_year_id">
        <option value="">كل السنوات</option>
        {#each data.academicYears as year}
          <option value={year.id} selected={String(year.id) === data.selectedAcademicYear}>{year.name.ar}</option>
        {/each}
      </select>
    </label>

    <label>
      <span>القسم</span>
      <select name="department_id">
        <option value="">كل الأقسام</option>
        {#each data.departments as department}
          <option value={department.id} selected={String(department.id) === data.selectedDepartment}>{department.name.ar}</option>
        {/each}
      </select>
    </label>

    <button type="submit">تطبيق الفلاتر</button>
  </form>

  {#if data.error}
    <p class="notice error" role="alert">{data.error}</p>
  {:else if data.courses.length === 0}
    <p class="notice">لا توجد دورات مطابقة للفلاتر الحالية.</p>
  {:else}
    <section class="course-grid" aria-label="الدورات المتاحة">
      {#each data.courses as course}
        <article class="course-card">
          <div class="course-mark" aria-hidden="true">{course.title.en.slice(0, 2).toUpperCase()}</div>
          <div class="course-copy">
            <p class="course-label">دورة متاحة</p>
            <h2>{course.title.ar}</h2>
            <p>{course.description.ar}</p>
          </div>
          <div class="course-footer">
            <div>
              {#if course.discount_cents > 0}
                <span class="old-price">{formatPrice(course.list_price_cents)}</span>
              {/if}
              <strong>{formatPrice(course.amount_due_cents)}</strong>
            </div>
            <a href={`/courses/${course.slug}`}>التفاصيل <span aria-hidden="true">←</span></a>
          </div>
        </article>
      {/each}
    </section>
    {#if data.pagination.last_page > 1}
      <nav class="pagination" aria-label="صفحات الدورات">
        {#if data.pagination.current_page > 1}
          <a href={pageHref(data.pagination.current_page - 1)}>السابق</a>
        {:else}
          <span class="disabled">السابق</span>
        {/if}
        <span>صفحة {data.pagination.current_page} من {data.pagination.last_page}</span>
        {#if data.pagination.current_page < data.pagination.last_page}
          <a href={pageHref(data.pagination.current_page + 1)}>التالي</a>
        {:else}
          <span class="disabled">التالي</span>
        {/if}
      </nav>
    {/if}
  {/if}
</main>

<style>
  :global(body) {
    margin: 0;
    background: #f3f7f6;
    color: #0f282f;
    font-family: 'IBM Plex Sans Arabic', Tahoma, sans-serif;
  }

  :global(*) { box-sizing: border-box; }

  .catalog-shell { max-width: 1180px; margin: 0 auto; padding: 1.25rem 1.25rem 4rem; }
  .catalog-header { display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0 3rem; }
  .brand { color: #0f282f; font-size: 1.05rem; font-weight: 800; letter-spacing: 0.08em; text-decoration: none; }
  .brand span { color: #17777a; font-size: 0.68rem; margin-inline-start: 0.35rem; }
  nav a { color: #17777a; font-weight: 700; text-decoration: none; }
  .intro { max-width: 700px; margin-bottom: 2rem; }
  .kicker, .course-label { color: #17777a; font-size: 0.78rem; font-weight: 800; letter-spacing: 0.04em; margin: 0 0 0.5rem; }
  h1 { font-size: clamp(2.2rem, 7vw, 4.8rem); line-height: 1.08; margin: 0; }
  .lede { color: #49636a; font-size: 1.05rem; line-height: 1.8; margin: 1rem 0 0; }
  .filters { align-items: end; background: #0f282f; border-radius: 0.75rem; color: white; display: grid; gap: 1rem; grid-template-columns: repeat(3, 1fr); margin-bottom: 2rem; padding: 1rem; }
  label { display: grid; gap: 0.4rem; }
  label span { font-size: 0.82rem; font-weight: 700; }
  select, button { border: 0; border-radius: 0.35rem; font: inherit; min-height: 2.8rem; padding: 0.5rem 0.75rem; }
  select { background: white; color: #0f282f; }
  button { background: #02eff0; color: #0f282f; cursor: pointer; font-weight: 800; }
  button:focus-visible, a:focus-visible, select:focus-visible { outline: 3px solid #02eff0; outline-offset: 3px; }
  .course-grid { display: grid; gap: 1rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .course-card { background: white; border: 1px solid #d9e6e4; border-radius: 0.6rem; display: flex; flex-direction: column; min-height: 300px; padding: 1rem; }
  .course-mark { align-items: center; background: #d9fafa; border-radius: 0.4rem; color: #0f282f; display: flex; font-weight: 900; height: 3.5rem; justify-content: center; width: 3.5rem; }
  .course-copy { flex: 1; padding-top: 1.5rem; }
  h2 { font-size: 1.35rem; margin: 0 0 0.65rem; }
  .course-copy p:last-child { color: #49636a; line-height: 1.7; margin: 0; }
  .course-footer { align-items: end; border-top: 1px solid #e5eeee; display: flex; justify-content: space-between; margin-top: 1.5rem; padding-top: 1rem; }
  .course-footer strong { display: block; font-size: 1.1rem; }
  .old-price { color: #799095; display: block; font-size: 0.78rem; text-decoration: line-through; }
  .course-footer a { color: #17777a; font-weight: 800; text-decoration: none; }
  .notice { background: white; border: 1px solid #d9e6e4; border-radius: 0.6rem; padding: 1.25rem; }
  .error { border-color: #b55a55; color: #8a302b; }
  .pagination { align-items: center; display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; }
  .pagination a, .pagination span { color: #17777a; font-weight: 800; }
  .pagination a { border: 1px solid #b9d4d2; border-radius: 0.35rem; padding: 0.55rem 0.85rem; text-decoration: none; }
  .pagination .disabled { color: #9aaeb0; }

  @media (max-width: 760px) {
    .catalog-shell { padding-inline: 1rem; }
    .catalog-header { padding-bottom: 2rem; }
    .filters, .course-grid { grid-template-columns: 1fr; }
    .filters button { width: 100%; }
  }
</style>