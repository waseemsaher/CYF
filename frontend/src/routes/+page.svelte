<script lang="ts">
  const items = ['الشهادات', 'الدورات', 'المحتوى', 'التقييمات'];
</script>

<svelte:head>
  <title>منصة FCAI</title>
</svelte:head>

<main class="page">
  <section class="hero">
    <p class="eyebrow">FCAI Courses</p>
    <h1>منصة تعليمية للطلاب</h1>
    <p>واجهة أساسية جاهزة لتطوير المرحلة القادمة.</p>
  </section>

  <section class="grid">
    {#each items as item}
      <article class="card">
        <h2>{item}</h2>
      </article>
    {/each}
  </section>
</main>

<style>
  :global(body) {
    margin: 0;
    font-family: 'Tahoma', sans-serif;
    background: #f5f7f8;
    color: #0f282f;
  }

  .page {
    padding: 2rem;
    max-width: 1100px;
    margin: 0 auto;
  }

  .hero {
    background: linear-gradient(135deg, #0f282f 0%, #02eff0 100%);
    color: white;
    border-radius: 1.5rem;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
  }

  .eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 0.8rem;
    opacity: 0.8;
  }

  h1 {
    font-size: clamp(2rem, 4vw, 3.5rem);
    margin: 0.5rem 0;
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
  }

  .card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 8px 18px rgba(15, 40, 47, 0.08);
  }

  .card h2 {
    margin: 0;
    font-size: 1.1rem;
  }
</style>
