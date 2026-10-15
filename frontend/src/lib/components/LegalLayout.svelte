<script lang="ts">
  import { currentLocale } from '$lib/i18n';

  interface Props {
    activeDoc: 'terms' | 'privacy' | 'refund';
    titleAr: string;
    titleEn: string;
    contentAr: string;
    contentEn: string;
    fallbackAr: string;
    fallbackEn: string;
    loading: boolean;
    selectedLang: 'ar' | 'en' | null;
    onSelectLang: (lang: 'ar' | 'en') => void;
  }

  let {
    activeDoc,
    titleAr,
    titleEn,
    contentAr,
    contentEn,
    fallbackAr,
    fallbackEn,
    loading,
    selectedLang,
    onSelectLang
  }: Props = $props();

  let activeLang = $derived(selectedLang ?? $currentLocale);
  let activeText = $derived(
    activeLang === 'en'
      ? (contentEn || fallbackEn)
      : (contentAr || fallbackAr)
  );

  interface LegalBlock {
    type: 'paragraph' | 'bullets';
    text?: string;
    items?: string[];
  }

  interface LegalSection {
    number: string;
    title: string;
    blocks: LegalBlock[];
  }

  interface ParsedDoc {
    intro: string;
    sections: LegalSection[];
    fallbackParagraphs: string[];
  }

  function parseLegalDoc(raw: string): ParsedDoc {
    if (!raw) {
      return { intro: '', sections: [], fallbackParagraphs: [] };
    }

    const lines = raw.split('\n');
    const introLines: string[] = [];
    const sections: LegalSection[] = [];
    let currentSection: LegalSection | null = null;
    let currentBlock: LegalBlock | null = null;

    for (const rawLine of lines) {
      const line = rawLine.trim();
      const sectionMatch = line.match(/^(\d+)\.\s+(.+)$/);

      if (sectionMatch) {
        if (currentSection) {
          sections.push(currentSection);
        }
        currentSection = {
          number: sectionMatch[1],
          title: sectionMatch[2],
          blocks: []
        };
        currentBlock = null;
        continue;
      }

      if (!currentSection) {
        if (line) {
          introLines.push(line);
        }
        continue;
      }

      if (!line) {
        currentBlock = null;
        continue;
      }

      const isBullet = line.startsWith('•') || line.startsWith('-') || line.startsWith('*');
      if (isBullet) {
        const bulletText = line.replace(/^[•\-\*]\s*/, '').trim();
        if (currentBlock && currentBlock.type === 'bullets' && currentBlock.items) {
          currentBlock.items.push(bulletText);
        } else {
          currentBlock = { type: 'bullets', items: [bulletText] };
          currentSection.blocks.push(currentBlock);
        }
      } else {
        if (currentBlock && currentBlock.type === 'paragraph' && currentBlock.text) {
          currentBlock.text += ' ' + line;
        } else {
          currentBlock = { type: 'paragraph', text: line };
          currentSection.blocks.push(currentBlock);
        }
      }
    }

    if (currentSection) {
      sections.push(currentSection);
    }

    const fallbackParagraphs = sections.length === 0
      ? raw.split(/\n\s*\n/).map(p => p.trim()).filter(Boolean)
      : [];

    return {
      intro: introLines.join('\n\n'),
      sections,
      fallbackParagraphs
    };
  }

  let parsed = $derived(parseLegalDoc(activeText));

  function renderTextParts(text: string): { isEmail: boolean; text: string }[] {
    const email = 'info@codeera.tech';
    if (!text.includes(email)) {
      return [{ isEmail: false, text }];
    }
    const parts = text.split(email);
    const result: { isEmail: boolean; text: string }[] = [];
    parts.forEach((p, idx) => {
      if (p) result.push({ isEmail: false, text: p });
      if (idx < parts.length - 1) {
        result.push({ isEmail: true, text: email });
      }
    });
    return result;
  }
</script>

<div class="legal-page" dir={activeLang === 'ar' ? 'rtl' : 'ltr'}>
  <div class="legal-container">
    <!-- Header -->
    <header class="legal-header">
      <div class="legal-header-top">
        <div class="doc-badge">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 2.18l7 3.12v4.7c0 4.67-3.13 9.04-7 10.18-3.87-1.14-7-5.51-7-10.18V6.3l7-3.12z"/>
          </svg>
          <span>{activeLang === 'en' ? 'Codeera Legal Documents' : 'الوثائق القانونية لمنصة Codeera'}</span>
        </div>

        <div class="lang-pills" role="group" aria-label={activeLang === 'en' ? 'Select language' : 'اختيار اللغة'}>
          <button
            type="button"
            class="lang-pill"
            class:active={activeLang === 'ar'}
            onclick={() => onSelectLang('ar')}
          >
            العربية
          </button>
          <button
            type="button"
            class="lang-pill"
            class:active={activeLang === 'en'}
            onclick={() => onSelectLang('en')}
          >
            English
          </button>
        </div>
      </div>

      <div class="legal-header-title-row">
        <h1>{activeLang === 'en' ? titleEn : titleAr}</h1>
        <p class="legal-date">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true">
            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
          </svg>
          <span>{activeLang === 'en' ? 'Last updated: October 2026' : 'آخر تحديث: أكتوبر 2026'}</span>
        </p>
      </div>

      <!-- Navigation Tabs Between Legal Documents -->
      <nav class="doc-tabs" aria-label={activeLang === 'en' ? 'Legal pages tabs' : 'تبويبات الصفحات القانونية'}>
        <a href="/terms" class="doc-tab" class:active={activeDoc === 'terms'}>
          {#if activeDoc === 'terms'}
            <span class="active-dot" aria-hidden="true"></span>
          {/if}
          <span>{activeLang === 'en' ? 'Terms & Conditions' : 'الشروط والأحكام'}</span>
        </a>
        <a href="/privacy" class="doc-tab" class:active={activeDoc === 'privacy'}>
          {#if activeDoc === 'privacy'}
            <span class="active-dot" aria-hidden="true"></span>
          {/if}
          <span>{activeLang === 'en' ? 'Privacy Policy' : 'سياسة الخصوصية'}</span>
        </a>
        <a href="/refund" class="doc-tab" class:active={activeDoc === 'refund'}>
          {#if activeDoc === 'refund'}
            <span class="active-dot" aria-hidden="true"></span>
          {/if}
          <span>{activeLang === 'en' ? 'Refund Policy' : 'سياسة الاسترجاع'}</span>
        </a>
      </nav>
    </header>

    <!-- Body / Content -->
    {#if loading}
      <div class="loading-skeleton" aria-busy="true" aria-label={activeLang === 'en' ? 'Loading document' : 'جاري التحميل'}>
        <div class="skeleton-lead"></div>
        <div class="skeleton-card">
          <div class="skeleton-title"></div>
          <div class="skeleton-p"></div>
          <div class="skeleton-p short"></div>
        </div>
        <div class="skeleton-card">
          <div class="skeleton-title"></div>
          <div class="skeleton-p"></div>
          <div class="skeleton-p"></div>
        </div>
      </div>
    {:else}
      <!-- Lead introduction if present -->
      {#if parsed.intro}
        <div class="legal-lead-card">
          <div class="lead-icon-box" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
            </svg>
          </div>
          <div class="lead-content">
            <p class="lead-text">{parsed.intro}</p>
          </div>
        </div>
      {/if}

      <!-- Numbered Sections -->
      {#if parsed.sections.length > 0}
        <div class="legal-sections">
          {#each parsed.sections as sec (sec.number)}
            <section class="legal-section-card">
              <div class="section-header">
                <span class="section-num">{sec.number.padStart(2, '0')}</span>
                <h2 class="section-title">{sec.title}</h2>
              </div>
              <div class="section-body">
                {#each sec.blocks as block}
                  {#if block.type === 'paragraph' && block.text}
                    <p class="legal-paragraph">
                      {#each renderTextParts(block.text) as part}
                        {#if part.isEmail}
                          <a href="mailto:{part.text}" class="legal-link">{part.text}</a>
                        {:else}
                          {part.text}
                        {/if}
                      {/each}
                    </p>
                  {:else if block.type === 'bullets' && block.items}
                    <ul class="legal-list">
                      {#each block.items as item}
                        <li class="legal-list-item">
                          <span class="bullet-dot" aria-hidden="true"></span>
                          <span class="bullet-text">
                            {#each renderTextParts(item) as part}
                              {#if part.isEmail}
                                <a href="mailto:{part.text}" class="legal-link">{part.text}</a>
                              {:else}
                                {part.text}
                              {/if}
                            {/each}
                          </span>
                        </li>
                      {/each}
                    </ul>
                  {/if}
                {/each}
              </div>
            </section>
          {/each}
        </div>
      {:else if parsed.fallbackParagraphs.length > 0}
        <!-- Fallback if unformatted text was provided -->
        <div class="legal-section-card">
          <div class="section-body">
            {#each parsed.fallbackParagraphs as p}
              <p class="legal-paragraph">
                {#each renderTextParts(p) as part}
                  {#if part.isEmail}
                    <a href="mailto:{part.text}" class="legal-link">{part.text}</a>
                  {:else}
                    {part.text}
                  {/if}
                {/each}
              </p>
            {/each}
          </div>
        </div>
      {/if}

      <!-- Contact Assistance Card -->
      <aside class="legal-contact-card">
        <div class="contact-info">
          <h3>{activeLang === 'en' ? 'Have questions or concerns?' : 'هل لديك أي استفسار أو تساؤل قانوني؟'}</h3>
          <p>{activeLang === 'en' ? 'Reach out directly to the Codeera administration team.' : 'تواصل مباشرة مع إدارة منصة Codeera وسيسعدنا الرد على استفسارك.'}</p>
        </div>
        <a href="mailto:info@codeera.tech" class="contact-btn">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
          </svg>
          <span>info@codeera.tech</span>
        </a>
      </aside>
    {/if}
  </div>
</div>

<style>
  .legal-page {
    background-color: var(--paper);
    min-height: calc(100vh - 140px);
    padding: 3.5rem 1.5rem 6rem;
    box-sizing: border-box;
  }

  .legal-container {
    max-width: 860px;
    margin-inline: auto;
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  /* Header */
  .legal-header {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid var(--line);
  }

  .legal-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .doc-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    background: rgba(34, 211, 238, 0.1);
    color: var(--brand-accent);
    border: 1px solid rgba(34, 211, 238, 0.25);
    font-size: 0.82rem;
    font-weight: 700;
  }

  .lang-pills {
    display: inline-flex;
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 9999px;
    padding: 0.25rem;
    gap: 0.3rem;
  }

  .lang-pill {
    padding: 0.35rem 0.95rem;
    font-size: 0.82rem;
    font-weight: 700;
    border-radius: 9999px;
    border: none;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .lang-pill:hover {
    color: var(--ink);
  }

  .lang-pill.active {
    background: var(--storm);
    color: var(--paper);
  }

  :global([data-theme='dark']) .lang-pill.active {
    background: var(--brand-accent);
    color: #0F282F;
  }

  .legal-header-title-row {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .legal-header-title-row h1 {
    font-size: 2.25rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
    letter-spacing: -0.02em;
    line-height: 1.25;
  }

  .legal-date {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.85rem;
    color: var(--muted);
    margin: 0;
  }

  /* Document Navigation Tabs */
  .doc-tabs {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
    margin-top: 0.25rem;
  }

  .doc-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 1.15rem;
    border-radius: 0.75rem;
    background: var(--card);
    border: 2px solid var(--line);
    color: var(--muted);
    font-size: 0.9rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.15s ease;
  }

  .doc-tab:hover {
    color: var(--ink);
    border-color: var(--brand-accent);
    background: var(--card-hover);
  }

  .doc-tab.active {
    background: rgba(34, 211, 238, 0.12);
    border-color: var(--brand-accent);
    color: var(--storm);
  }

  .active-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--brand-accent);
    box-shadow: 0 0 6px var(--brand-accent);
  }

  /* Lead Introduction Card */
  .legal-lead-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-inline-start: 4px solid var(--brand-accent);
    border-radius: 1rem;
    padding: 1.5rem 1.75rem;
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
  }

  .lead-icon-box {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    background: rgba(34, 211, 238, 0.1);
    color: var(--brand-accent);
    border: 1px solid rgba(34, 211, 238, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .lead-content {
    flex: 1;
  }

  .lead-text {
    margin: 0;
    font-size: 1.05rem;
    line-height: 1.85;
    color: var(--ink);
    font-weight: 500;
  }

  /* Sections */
  .legal-sections {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .legal-section-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1rem;
    padding: 1.75rem 2rem;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }

  .legal-section-card:hover {
    border-color: var(--brand-accent);
  }

  .section-header {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-bottom: 1.25rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid var(--line-subtle, var(--line));
  }

  .section-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.35rem;
    height: 2.35rem;
    padding: 0 0.5rem;
    border-radius: 0.55rem;
    background: rgba(34, 211, 238, 0.12);
    color: var(--brand-accent);
    font-weight: 800;
    font-size: 0.95rem;
    font-feature-settings: 'tnum';
    border: 1px solid rgba(34, 211, 238, 0.25);
    flex-shrink: 0;
  }

  .section-title {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--storm);
    letter-spacing: -0.01em;
    line-height: 1.35;
  }

  .section-body {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .legal-paragraph {
    margin: 0;
    font-size: 0.98rem;
    line-height: 1.9;
    color: var(--ink);
  }

  .legal-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
  }

  .legal-list-item {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    font-size: 0.98rem;
    line-height: 1.85;
    color: var(--ink);
  }

  .bullet-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--brand-accent);
    margin-top: 0.65rem;
    flex-shrink: 0;
    box-shadow: 0 0 6px rgba(34, 211, 238, 0.6);
  }

  .bullet-text {
    flex: 1;
  }

  .legal-link {
    color: var(--brand-accent);
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 3px;
    transition: opacity 0.15s ease;
  }

  .legal-link:hover {
    opacity: 0.8;
  }

  /* Contact Card */
  .legal-contact-card {
    background: var(--card);
    border: 2px dashed var(--line);
    border-radius: 1rem;
    padding: 1.75rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-top: 1rem;
  }

  .contact-info h3 {
    margin: 0 0 0.35rem;
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--storm);
  }

  .contact-info p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--muted);
  }

  .contact-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.65rem 1.35rem;
    background: var(--storm);
    color: var(--paper);
    border-radius: 0.65rem;
    font-size: 0.92rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.15s ease;
  }

  .contact-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  :global([data-theme='dark']) .contact-btn {
    background: var(--brand-accent);
    color: #0F282F;
  }

  :global([data-theme='dark']) .contact-btn:hover {
    background: var(--vivid-cyan-hover);
  }

  /* Skeleton Loading */
  .loading-skeleton {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .skeleton-lead {
    height: 5rem;
    background: var(--line);
    border-radius: 1rem;
    opacity: 0.25;
    animation: pulse-skeleton 1.2s ease-in-out infinite;
  }

  .skeleton-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1rem;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .skeleton-title {
    height: 1.5rem;
    width: 40%;
    background: var(--line);
    border-radius: 0.4rem;
    opacity: 0.35;
    animation: pulse-skeleton 1.2s ease-in-out infinite;
  }

  .skeleton-p {
    height: 1rem;
    width: 90%;
    background: var(--line);
    border-radius: 0.35rem;
    opacity: 0.25;
    animation: pulse-skeleton 1.2s ease-in-out infinite;
    animation-delay: 0.15s;
  }

  .skeleton-p.short {
    width: 60%;
  }

  @keyframes pulse-skeleton {
    0%, 100% { opacity: 0.2; }
    50% { opacity: 0.35; }
  }

  /* Responsive Adjustments */
  @media (max-width: 640px) {
    .legal-page {
      padding: 2rem 1rem 4rem;
    }

    .legal-container {
      gap: 1.5rem;
    }

    .legal-header {
      gap: 1.25rem;
      padding-bottom: 1.25rem;
    }

    .legal-header-title-row h1 {
      font-size: 1.75rem;
    }

    .doc-tabs {
      overflow-x: auto;
      flex-wrap: nowrap;
      padding-bottom: 0.35rem;
      scrollbar-width: none;
    }

    .doc-tabs::-webkit-scrollbar {
      display: none;
    }

    .doc-tab {
      white-space: nowrap;
      padding: 0.45rem 0.95rem;
      font-size: 0.85rem;
    }

    .legal-lead-card {
      padding: 1.25rem 1.15rem;
      gap: 0.85rem;
    }

    .lead-icon-box {
      width: 2.25rem;
      height: 2.25rem;
    }

    .lead-text {
      font-size: 0.95rem;
    }

    .legal-section-card {
      padding: 1.35rem 1.15rem;
      border-radius: 0.85rem;
    }

    .section-title {
      font-size: 1.15rem;
    }

    .legal-paragraph,
    .legal-list-item {
      font-size: 0.92rem;
    }

    .legal-contact-card {
      padding: 1.35rem 1.15rem;
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .contact-btn {
      width: 100%;
      justify-content: center;
    }
  }
</style>

