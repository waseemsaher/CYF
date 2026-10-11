<script lang="ts">
  import { testimonials } from '$lib/data/testimonials';

  const visibleTestimonials = testimonials.filter((item) => item.hasConsent && item.quoteAr.trim().length > 0);
</script>

{#if visibleTestimonials.length > 0}
  <section class="relative bg-slate-50 py-20 px-4">
    <div class="mx-auto max-w-6xl">
      <div class="mb-12 text-center">
        <span class="mb-3 inline-block rounded-full bg-brand-navy/10 px-4 py-1 text-xs font-extrabold text-brand-navy">
          تجارب الطلاب
        </span>
        <h2 class="text-2xl font-extrabold text-brand-navy sm:text-4xl">
          قالوا إيه عن المذاكرة على Codeera
        </h2>
      </div>

      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {#each visibleTestimonials as item (item.id)}
          <article class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="mb-6 text-sm leading-relaxed text-slate-700">
              "{item.quoteAr}"
            </p>
            <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-navy text-xs font-bold text-white">
                {item.initials}
              </div>
              <div>
                <p class="text-sm font-bold text-brand-navy">{item.initials}</p>
                <p class="text-xs text-slate-500">
                  {item.yearAr}{item.departmentAr ? ` • ${item.departmentAr}` : ''}{item.courseTitleAr ? ` — ${item.courseTitleAr}` : ''}
                </p>
              </div>
            </div>
          </article>
        {/each}
      </div>
    </div>
  </section>
{/if}
