<script lang="ts">
  import type { Snippet } from 'svelte';
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { currentUser, refreshUser, logout } from '$lib/api/auth';
  import { initLocale, currentLocale, toggleLocale, t } from '$lib/i18n';
  import { FEATURES } from '$lib/config';
  import { socialLinks } from '$lib/socials';
  import { pwaInfo } from 'virtual:pwa-info';

  let { children }: { children: Snippet } = $props();

  let isDark = $state(false);
  let mobileMenuOpen = $state(false);
  let webManifestLink = $derived(pwaInfo ? pwaInfo.webManifest.linkTag : '');

  function toggleMobileMenu() {
    mobileMenuOpen = !mobileMenuOpen;
  }

  function closeMobileMenu() {
    mobileMenuOpen = false;
  }

  $effect(() => {
    const _ = page.url.pathname;
    mobileMenuOpen = false;
  });

  onMount(async () => {
    initLocale();
    refreshUser(fetch);
    if (pwaInfo) {
      try {
        const { registerSW } = await import('virtual:pwa-register');
        registerSW({
          immediate: true,
          onRegistered(r?: ServiceWorkerRegistration) {
            console.log('Codeera PWA Service Worker registered:', r);
          },
          onRegisterError(error?: unknown) {
            console.error('Codeera PWA Service Worker registration error:', error);
          }
        });
      } catch (err) {
        console.error('Failed to register Codeera PWA Service Worker:', err);
      }
    }
    if (typeof document !== 'undefined') {
      const activeTheme = document.documentElement.getAttribute('data-theme') ||
                          localStorage.getItem('codeera_theme') ||
                          localStorage.getItem('coderaa_theme') ||
                          localStorage.getItem('fcai_theme') ||
                          (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      isDark = activeTheme === 'dark';
      document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
      document.documentElement.classList.toggle('dark', isDark);
    }
  });

  function toggleTheme() {
    isDark = !isDark;
    const nextTheme = isDark ? 'dark' : 'light';
    if (typeof document !== 'undefined') {
      document.documentElement.setAttribute('data-theme', nextTheme);
      document.documentElement.classList.toggle('dark', isDark);
      try {
        localStorage.setItem('codeera_theme', nextTheme);
      } catch (_) {}
    }
  }

  function handleLogout() {
    logout();
    window.location.href = '/';
  }

  let navLinks = $derived.by(() => {
    const tr = $t.nav;
    const links = [
      { href: '/', label: tr.home },
      { href: '/courses', label: tr.courses },
    ];
    const user = $currentUser;
    if (!user) return links;

    const role = user.role || (user.roles && user.roles[0]) || '';

    if (role === 'superadmin' || role === 'admin') {
      links.push({ href: '/admin', label: tr.admin });
      links.push({ href: '/admin/payments', label: tr.reviewQueue });
    } else if (role === 'teacher') {
      links.push({ href: '/teacher', label: tr.teacher });
    } else {
      links.push({ href: '/dashboard', label: tr.dashboard });
      links.push({ href: '/payments', label: tr.payments });
    }

    return links;
  });

  function getRoleLabel(role?: string): string {
    const roles = $t.nav.roles;
    if (role === 'superadmin') return roles.superadmin;
    if (role === 'admin') return roles.admin;
    if (role === 'teacher') return roles.teacher;
    return roles.student;
  }

  function isActive(href: string): boolean {
    if (href === '/') {
      return page.url.pathname === '/';
    }
    return page.url.pathname.startsWith(href);
  }
</script>

<svelte:head>
  <!-- eslint-disable-next-line svelte/no-at-html-tags -->
  {@html webManifestLink}
</svelte:head>

<a href="#main-content" class="skip-link">{$t.nav.skipLink}</a>

<div class="layout-container">
  <header class="global-header">
    <div class="header-inner">
      <a class="brand" href="/" aria-label={$t.nav.brandAria} onclick={closeMobileMenu}>
        <img src="/logo/app-icon.svg" alt="" aria-hidden="true" class="brand-logo" width="36" height="36" />
        <span class="brand-text">Codeera</span>
      </a>

      <!-- Desktop nav -->
      <nav aria-label={$t.nav.mainNavAria} class="nav-menu desktop-only">
        {#each navLinks as link}
          <a
            href={link.href}
            class="nav-link"
            class:active={isActive(link.href)}
            aria-current={isActive(link.href) ? 'page' : undefined}
          >
            {link.label}
          </a>
        {/each}
      </nav>

      <!-- Desktop actions -->
      <div class="header-actions desktop-only">
        <button
          type="button"
          class="btn-theme-toggle"
          onclick={toggleTheme}
          aria-label={isDark ? $t.nav.themeToggleAriaLight : $t.nav.themeToggleAriaDark}
          title={isDark ? $t.nav.themeLight : $t.nav.themeDark}
        >
          {#if isDark}
            <svg class="theme-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="5"></circle>
              <line x1="12" y1="1" x2="12" y2="3"></line>
              <line x1="12" y1="21" x2="12" y2="23"></line>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
              <line x1="1" y1="12" x2="3" y2="12"></line>
              <line x1="21" y1="12" x2="23" y2="12"></line>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
          {:else}
            <svg class="theme-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
          {/if}
        </button>

        {#if FEATURES.ENABLE_LANGUAGE_TOGGLE}
          <button
            type="button"
            class="btn-lang-toggle"
            onclick={toggleLocale}
            aria-label={$t.nav.langToggleAria}
            title={$t.nav.langToggleLabel}
          >
            {$t.nav.langToggleLabel}
          </button>
        {/if}

        {#if $currentUser}
          <div class="user-badge">
            <span class="user-name">{$currentUser.name}</span>
            <span class="role-chip">{getRoleLabel($currentUser.role)}</span>
            <button type="button" class="btn-logout" onclick={handleLogout} title={$t.nav.logout}>
              {$t.nav.logout}
            </button>
          </div>
        {:else}
          <a href="/login" class="btn-auth-login">{$t.nav.login}</a>
          <a href="/register" class="btn-auth-register">{$t.nav.register}</a>
        {/if}
      </div>

      <!-- Mobile quick controls (compact bar: theme toggle + hamburger) -->
      <div class="mobile-header-controls mobile-only">
        <button
          type="button"
          class="btn-theme-toggle"
          onclick={toggleTheme}
          aria-label={isDark ? $t.nav.themeToggleAriaLight : $t.nav.themeToggleAriaDark}
          title={isDark ? $t.nav.themeLight : $t.nav.themeDark}
        >
          {#if isDark}
            <svg class="theme-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="5"></circle>
              <line x1="12" y1="1" x2="12" y2="3"></line>
              <line x1="12" y1="21" x2="12" y2="23"></line>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
              <line x1="1" y1="12" x2="3" y2="12"></line>
              <line x1="21" y1="12" x2="23" y2="12"></line>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
          {:else}
            <svg class="theme-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
          {/if}
        </button>

        <button
          type="button"
          class="btn-menu-toggle"
          onclick={toggleMobileMenu}
          aria-expanded={mobileMenuOpen}
          aria-label={mobileMenuOpen ? ($t.nav.menuCloseAria || 'إغلاق القائمة') : ($t.nav.menuToggleAria || 'فتح القائمة')}
        >
          {#if mobileMenuOpen}
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          {:else}
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
          {/if}
        </button>
      </div>
    </div>

    <!-- Mobile Drawer Panel -->
    {#if mobileMenuOpen}
      <div class="mobile-drawer mobile-only">
        <nav class="mobile-nav-list" aria-label={$t.nav.mainNavAria}>
          {#each navLinks as link}
            <a
              href={link.href}
              class="mobile-nav-link"
              class:active={isActive(link.href)}
              aria-current={isActive(link.href) ? 'page' : undefined}
              onclick={closeMobileMenu}
            >
              {link.label}
            </a>
          {/each}
        </nav>

        <div class="mobile-drawer-footer">
          {#if FEATURES.ENABLE_LANGUAGE_TOGGLE}
            <button
              type="button"
              class="btn-lang-toggle mobile-lang-btn"
              onclick={() => { toggleLocale(); closeMobileMenu(); }}
              aria-label={$t.nav.langToggleAria}
            >
              {$t.nav.langToggleLabel}
            </button>
          {/if}

          {#if $currentUser}
            <div class="mobile-user-section">
              <div class="mobile-user-info">
                <span class="user-name">{$currentUser.name}</span>
                <span class="role-chip">{getRoleLabel($currentUser.role)}</span>
              </div>
              <button
                type="button"
                class="btn-logout mobile-logout-btn"
                onclick={() => { closeMobileMenu(); handleLogout(); }}
              >
                {$t.nav.logout}
              </button>
            </div>
          {:else}
            <div class="mobile-auth-actions">
              <a href="/login" class="btn-auth-login mobile-auth-btn" onclick={closeMobileMenu}>{$t.nav.login}</a>
              <a href="/register" class="btn-auth-register mobile-auth-btn" onclick={closeMobileMenu}>{$t.nav.register}</a>
            </div>
          {/if}
        </div>
      </div>
    {/if}
  </header>

  <main id="main-content" class="main-content" tabindex="-1">
    {@render children()}
  </main>

  <footer class="global-footer">
    <div class="footer-inner">
      <div class="footer-start">
        <a href="/" class="footer-brand" aria-label={$t.nav.brandAria}>
          <span class="footer-logo-fcai">CODE</span>
          <span class="footer-logo-courses">ERA</span>
        </a>
        <span class="footer-sep" aria-hidden="true">|</span>
        <span class="footer-tagline">{$t.nav.footer.tagline}</span>
      </div>

      <div class="footer-social" aria-label={$t.nav.footer.socialAria}>
        <a
          href={socialLinks[0].url}
          target="_blank"
          rel="noopener noreferrer"
          class="social-btn social-facebook"
          aria-label={socialLinks[0].ariaLabel}
          title={socialLinks[0].nameAr}
        >
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
          </svg>
        </a>

        <a
          href={socialLinks[1].url}
          target="_blank"
          rel="noopener noreferrer"
          class="social-btn social-youtube"
          aria-label={socialLinks[1].ariaLabel}
          title={socialLinks[1].nameAr}
        >
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
          </svg>
        </a>

        <a
          href={socialLinks[2].url}
          target="_blank"
          rel="noopener noreferrer"
          class="social-btn social-whatsapp"
          aria-label={socialLinks[2].ariaLabel}
          title={socialLinks[2].nameAr}
        >
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
          </svg>
        </a>

        <a
          href={socialLinks[3].url}
          target="_blank"
          rel="noopener noreferrer"
          class="social-btn social-telegram"
          aria-label={socialLinks[3].ariaLabel}
          title={socialLinks[3].nameAr}
        >
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .37z"/>
          </svg>
        </a>
      </div>

      <nav class="footer-links" aria-label={$t.nav.footer.legalNavAria}>
        <a href="/terms">{$t.nav.footer.terms}</a>
        <span class="separator" aria-hidden="true">•</span>
        <a href="/privacy">{$t.nav.footer.privacy}</a>
        <span class="separator" aria-hidden="true">•</span>
        <a href="/refund">{$t.nav.footer.refund}</a>
      </nav>

      <div class="footer-copy">
        <small>© 2026 Codeera. {$t.nav.footer.rights}</small>
      </div>
    </div>
  </footer>
</div>

<style>
  :global(:root) {
    /* Brand Design Tokens (docs/REQUIREMENTS.md §14) */
    --storm-green: #0F282F;
    --storm-green-rgb: 15, 40, 47;
    --vivid-cyan: #02EFF0;
    --vivid-cyan-rgb: 2, 239, 240;
    --vivid-cyan-hover: #3df3f4;
    --vivid-cyan-dark: #00b6b7;

    /* Primary User Color Identity Tokens */
    --bg-primary: #E5E2DD;
    --bg-card: #FAF8F5;
    --bg-secondary: #D5CBC1;
    --text-main: #1A1918;
    --text-muted: #6B6864;
    --brand-navy: #2A3B6A;
    --brand-accent: #C82B34;

    /* RGB triplets for opacity blending */
    --brand-navy-rgb: 42, 59, 106;
    --brand-accent-rgb: 200, 43, 52;
    --bg-secondary-rgb: 213, 203, 193;

    /* Semantic Aliases mapped directly to user tokens */
    --storm: #1A1918;
    --cyan: #C82B34;
    --ink: #1A1918;
    --muted: #6B6864;
    --paper: #E5E2DD;
    --card: #FAF8F5;
    --card-hover: #F2ECE4;
    --line: #D5CBC1;
    --line-subtle: #E2DAD0;
    --line-bold: #1A1918;
    --deep-cyan: #2A3B6A;
    --border-width: 2px;
  }

  :global(:root[data-theme='dark']),
  :global(html[data-theme='dark']),
  :global(html.dark),
  :global([data-theme='dark']) {
    /* Brand Design Tokens (docs/REQUIREMENTS.md §14) */
    --storm-green: #0F282F;
    --storm-green-rgb: 15, 40, 47;
    --vivid-cyan: #02EFF0;
    --vivid-cyan-rgb: 2, 239, 240;
    --vivid-cyan-hover: #3df3f4;
    --vivid-cyan-dark: #00b6b7;

    --bg-primary: #121211;
    --bg-card: #1A1918;
    --bg-secondary: #272523;
    --text-main: #FAF8F5;
    --text-muted: #9E9A94;
    --brand-navy: #5B7AC7;
    --brand-accent: #E8454F;

    --brand-navy-rgb: 91, 122, 199;
    --brand-accent-rgb: 232, 69, 79;

    --storm: #FAF8F5;
    --cyan: #E8454F;
    --ink: #FAF8F5;
    --muted: #9E9A94;
    --paper: #121211;
    --card: #1A1918;
    --card-hover: #242220;
    --line: #33302C;
    --line-subtle: #292623;
    --line-bold: #E8454F;
    --deep-cyan: #5B7AC7;
    --border-width: 2px;
    color-scheme: dark;
  }

  :global(html),
  :global(body) {
    background-color: var(--paper);
    color: var(--ink);
    font-family: 'IBM Plex Sans Arabic', Tahoma, sans-serif;
    margin: 0;
    padding: 0;
    line-height: 1.6;
    direction: rtl;
    text-align: start;
    transition: background-color 150ms ease, color 150ms ease;
    overflow-x: hidden;
    max-width: 100vw;
  }

  /* Universal Dark Theme Overrides for all Cards, Surfaces & Forms */
  :global([data-theme='dark'] .card),
  :global([data-theme='dark'] .auth-card),
  :global([data-theme='dark'] .guard-card),
  :global([data-theme='dark'] .welcome-banner),
  :global([data-theme='dark'] .profile-card),
  :global([data-theme='dark'] .dash-section),
  :global([data-theme='dark'] .learning-card),
  :global([data-theme='dark'] .legal-card),
  :global([data-theme='dark'] .legal-container),
  :global([data-theme='dark'] .legal-content),
  :global([data-theme='dark'] .kpi-card),
  :global([data-theme='dark'] .course-card),
  :global([data-theme='dark'] .quiz-card),
  :global([data-theme='dark'] .quiz-header),
  :global([data-theme='dark'] .score-card),
  :global([data-theme='dark'] .explanation-box),
  :global([data-theme='dark'] .section-panel),
  :global([data-theme='dark'] .section-card),
  :global([data-theme='dark'] .admin-header),
  :global([data-theme='dark'] .dash-panel),
  :global([data-theme='dark'] .modal-card),
  :global([data-theme='dark'] .payment-card),
  :global([data-theme='dark'] .queue-card),
  :global([data-theme='dark'] .enrollment-panel),
  :global([data-theme='dark'] .admin-panel),
  :global([data-theme='dark'] .content-panel),
  :global([data-theme='dark'] .checkout-form),
  :global([data-theme='dark'] .success-panel),
  :global([data-theme='dark'] .notice),
  :global([data-theme='dark'] .empty-state),
  :global([data-theme='dark'] .telegram-card),
  :global([data-theme='dark'] .tab-btn),
  :global([data-theme='dark'] .step-card),
  :global([data-theme='dark'] .feature-item),
  :global([data-theme='dark'] .faq-card),
  :global([data-theme='dark'] .status-tabs),
  :global([data-theme='dark'] .pending-item),
  :global([data-theme='dark'] .question-card),
  :global([data-theme='dark'] .payouts-table),
  :global([data-theme='dark'] .students-table) {
    background-color: var(--card) !important;
    color: var(--ink) !important;
    border-color: var(--line) !important;
  }

  /* Prevent buttons that use dark backgrounds from becoming white in dark mode */
  :global([data-theme='dark']) .btn-primary,
  :global([data-theme='dark']) .btn-submit,
  :global([data-theme='dark']) .btn-submit-quiz,
  :global([data-theme='dark']) .btn-auth-register,
  :global([data-theme='dark']) .btn-card-explore,
  :global([data-theme='dark']) .btn-explore-course,
  :global([data-theme='dark']) .btn-adm-edit,
  :global([data-theme='dark']) .btn-add-course,
  :global([data-theme='dark']) .btn-apply-filters,
  :global([data-theme='dark']) .btn-manage-content,
  :global([data-theme='dark']) .btn-guard-primary,
  :global([data-theme='dark']) .btn-payments-queue,
  :global([data-theme='dark']) .btn-open-modal,
  :global([data-theme='dark']) .btn-login-cta,
  :global([data-theme='dark']) .btn-go-course,
  :global([data-theme='dark']) .btn-browse-courses,
  :global([data-theme='dark']) .btn-view-content {
    background-color: var(--brand-accent) !important;
    color: #ffffff !important;
    border-color: var(--brand-accent) !important;
    font-weight: 800 !important;
  }

  :global([data-theme='dark']) .btn-primary:hover,
  :global([data-theme='dark']) .btn-submit:hover,
  :global([data-theme='dark']) .btn-submit-quiz:hover,
  :global([data-theme='dark']) .btn-auth-register:hover,
  :global([data-theme='dark']) .btn-card-explore:hover,
  :global([data-theme='dark']) .btn-explore-course:hover,
  :global([data-theme='dark']) .btn-adm-edit:hover,
  :global([data-theme='dark']) .btn-add-course:hover,
  :global([data-theme='dark']) .btn-apply-filters:hover,
  :global([data-theme='dark']) .btn-manage-content:hover,
  :global([data-theme='dark']) .btn-guard-primary:hover,
  :global([data-theme='dark']) .btn-payments-queue:hover,
  :global([data-theme='dark']) .btn-open-modal:hover,
  :global([data-theme='dark']) .btn-login-cta:hover,
  :global([data-theme='dark']) .btn-go-course:hover,
  :global([data-theme='dark']) .btn-browse-courses:hover,
  :global([data-theme='dark']) .btn-view-content:hover {
    opacity: 0.92 !important;
    box-shadow: 0 0 18px rgba(232, 69, 79, 0.45) !important;
  }

  /* Universal Contrast: Text on Accent buttons MUST ALWAYS be crisp white (#ffffff) */
  :global(.btn-hero-primary),
  :global(.btn-cta-primary),
  :global(.submit-btn),
  :global([data-theme='dark'] .btn-hero-primary),
  :global([data-theme='dark'] .btn-cta-primary),
  :global([data-theme='dark'] .submit-btn) {
    color: #ffffff !important;
  }

  /* Dark mode active tabs */
  :global([data-theme='dark']) .status-tabs button.active,
  :global([data-theme='dark']) .tab-btn.active {
    background-color: var(--brand-navy) !important;
    color: #ffffff !important;
    border-color: var(--brand-navy) !important;
    font-weight: 800 !important;
  }

  /* Dark mode summary and dark containers */
  :global([data-theme='dark']) .order-summary {
    background: #141312 !important;
    color: #FAF8F5 !important;
    border: 2px solid var(--line) !important;
  }

  /* Dark mode badges, avatars & chips */
  :global([data-theme='dark']) .profile-avatar {
    background: rgba(91, 122, 199, 0.15) !important;
    color: var(--deep-cyan) !important;
    border-color: var(--deep-cyan) !important;
  }
  :global([data-theme='dark']) .role-chip {
    background: rgba(91, 122, 199, 0.12) !important;
    color: var(--deep-cyan) !important;
    border: 1px solid rgba(91, 122, 199, 0.3) !important;
  }
  :global([data-theme='dark']) .btn-edit-course,
  :global([data-theme='dark']) .btn-adm-content,
  :global([data-theme='dark']) .btn-link {
    background: rgba(91, 122, 199, 0.1) !important;
    color: var(--deep-cyan) !important;
    border-color: var(--line) !important;
  }
  :global([data-theme='dark']) .section-header {
    background: #141312 !important;
    border-color: var(--line) !important;
  }
  :global([data-theme='dark']) .item-icon {
    background: #1F1D1B !important;
    color: var(--deep-cyan) !important;
  }
  :global([data-theme='dark']) .item-row:hover {
    background: var(--card-hover) !important;
  }
  :global([data-theme='dark']) .data-table th {
    background: #141312 !important;
    color: var(--storm) !important;
  }
  :global([data-theme='dark']) .data-table tr:hover td {
    background: var(--card-hover) !important;
  }
  :global([data-theme='dark']) .pending-section {
    background: rgba(245, 158, 11, 0.1) !important;
    border-color: rgba(245, 158, 11, 0.35) !important;
  }
  :global([data-theme='dark']) .status-linked-box {
    background: rgba(16, 185, 129, 0.12) !important;
    border-color: rgba(16, 185, 129, 0.35) !important;
  }
  :global([data-theme='dark']) .linked-text {
    color: #a7f3d0 !important;
  }
  :global([data-theme='dark']) .linked-text strong {
    color: #6ee7b7 !important;
  }
  :global([data-theme='dark']) .deep-link-box {
    background: rgba(42, 59, 106, 0.18) !important;
    border-color: rgba(91, 122, 199, 0.35) !important;
  }
  :global([data-theme='dark']) .deep-link-hint {
    color: #9cb5f0 !important;
  }
  :global([data-theme='dark']) .lock-bubble {
    background: rgba(91, 122, 199, 0.1) !important;
    color: var(--deep-cyan) !important;
    border-color: rgba(91, 122, 199, 0.3) !important;
  }
  :global([data-theme='dark']) .shield-bubble {
    background: rgba(245, 158, 11, 0.12) !important;
    color: #fbbf24 !important;
    border-color: rgba(245, 158, 11, 0.3) !important;
  }
  :global([data-theme='dark']) .alert-bubble {
    background: rgba(239, 68, 68, 0.12) !important;
    color: #f87171 !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
  }
  :global([data-theme='dark']) .score-icon.pass {
    background: rgba(16, 185, 129, 0.12) !important;
    color: #34d399 !important;
    border-color: rgba(16, 185, 129, 0.3) !important;
  }
  :global([data-theme='dark']) .score-icon.fail {
    background: rgba(239, 68, 68, 0.12) !important;
    color: #f87171 !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
  }
  :global([data-theme='dark']) .review-option {
    background: var(--card) !important;
    color: var(--muted) !important;
  }
  :global([data-theme='dark']) .review-option.is-correct {
    background: rgba(16, 185, 129, 0.12) !important;
    border-color: #059669 !important;
    color: #ecfdf5 !important;
  }
  :global([data-theme='dark']) .review-option.is-wrong {
    background: rgba(239, 68, 68, 0.12) !important;
    border-color: #dc2626 !important;
    color: #fca5a5 !important;
  }
  :global([data-theme='dark']) .error-banner,
  :global([data-theme='dark']) .inline-error {
    background: rgba(200, 43, 52, 0.12) !important;
    border-color: rgba(200, 43, 52, 0.4) !important;
    color: #fca5a5 !important;
  }
  :global([data-theme='dark']) .badge-pending {
    background: rgba(245, 158, 11, 0.15) !important;
    color: #fde68a !important;
  }
  :global([data-theme='dark']) .badge-approved {
    background: rgba(16, 185, 129, 0.15) !important;
    color: #a7f3d0 !important;
  }
  :global([data-theme='dark']) .badge-rejected {
    background: rgba(239, 68, 68, 0.15) !important;
    color: #fca5a5 !important;
  }
  :global([data-theme='dark']) .badge-role {
    background: rgba(91, 122, 199, 0.12) !important;
    color: var(--deep-cyan) !important;
    border-color: rgba(91, 122, 199, 0.3) !important;
  }
  :global([data-theme='dark']) .counter-badge {
    background: rgba(200, 43, 52, 0.15) !important;
    color: var(--brand-accent) !important;
  }
  :global([data-theme='dark']) .counter-badge.pending-badge {
    background: rgba(245, 158, 11, 0.2) !important;
    color: #fde68a !important;
  }
  :global([data-theme='dark']) .course-badge {
    background: rgba(16, 185, 129, 0.18) !important;
    color: #6ee7b7 !important;
    border: 1px solid rgba(16, 185, 129, 0.3) !important;
  }
  :global([data-theme='dark']) .pending-status-chip {
    background: rgba(245, 158, 11, 0.2) !important;
    color: #fde68a !important;
    border-color: rgba(245, 158, 11, 0.4) !important;
  }
  :global([data-theme='dark']) .telegram-icon-wrapper {
    background: rgba(42, 59, 106, 0.25) !important;
    color: #9cb5f0 !important;
    border: 1px solid rgba(91, 122, 199, 0.35) !important;
  }
  :global([data-theme='dark']) .role-badge,
  :global([data-theme='dark']) .share-badge {
    background: rgba(91, 122, 199, 0.12) !important;
    color: var(--deep-cyan) !important;
    border-color: rgba(91, 122, 199, 0.3) !important;
  }
  :global([data-theme='dark']) .students-badge {
    background: rgba(16, 185, 129, 0.15) !important;
    color: #6ee7b7 !important;
  }
  :global([data-theme='dark']) .option-label {
    background: var(--card) !important;
    color: var(--storm) !important;
  }
  :global([data-theme='dark']) .option-label:hover {
    background: var(--card-hover) !important;
  }
  :global([data-theme='dark']) .option-label.selected,
  :global([data-theme='dark']) .radio-label.selected {
    background: rgba(91, 122, 199, 0.15) !important;
    border-color: var(--deep-cyan) !important;
    color: var(--deep-cyan) !important;
  }
  :global([data-theme='dark']) .btn-reset-filters {
    background: rgba(239, 68, 68, 0.12) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
    color: #fca5a5 !important;
  }
  :global([data-theme='dark']) .btn-logout:hover {
    background: rgba(239, 68, 68, 0.18) !important;
  }
  :global([data-theme='dark']) .user-avatar {
    background: #1F1D1B !important;
    border-color: var(--line) !important;
  }
  :global([data-theme='dark']) .btn-unlink {
    background: #181313 !important;
    color: #f87171 !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
  }
  :global([data-theme='dark']) .btn-unlink:hover {
    background: rgba(239, 68, 68, 0.2) !important;
  }

  :global([data-theme='dark'] input),
  :global([data-theme='dark'] select),
  :global([data-theme='dark'] textarea) {
    background-color: #141312 !important;
    color: var(--storm) !important;
    border-color: var(--line) !important;
  }

  :global([data-theme='dark'] input::placeholder),
  :global([data-theme='dark'] textarea::placeholder) {
    color: #7A756E !important;
  }

  :global([data-theme='dark'] h1),
  :global([data-theme='dark'] h2),
  :global([data-theme='dark'] h3),
  :global([data-theme='dark'] h4),
  :global([data-theme='dark'] strong) {
    color: var(--storm) !important;
  }

  :global([data-theme='dark'] .btn-secondary),
  :global([data-theme='dark'] .btn-guard-secondary),
  :global([data-theme='dark'] .btn-cancel),
  :global([data-theme='dark'] .btn-card-view),
  :global([data-theme='dark'] .btn-adm-view),
  :global([data-theme='dark'] .btn-profile-link) {
    background-color: var(--card) !important;
    color: var(--storm) !important;
    border-color: var(--line) !important;
  }

  :global([data-theme='dark'] .btn-secondary:hover),
  :global([data-theme='dark'] .btn-guard-secondary:hover),
  :global([data-theme='dark'] .btn-cancel:hover),
  :global([data-theme='dark'] .btn-card-view:hover),
  :global([data-theme='dark'] .btn-adm-view:hover),
  :global([data-theme='dark'] .btn-profile-link:hover) {
    background-color: var(--card-hover) !important;
    border-color: var(--deep-cyan) !important;
  }

  :global([data-theme='dark'] .dev-hint-box),
  :global([data-theme='dark'] .empty-state-box),
  :global([data-theme='dark'] .admin-info-box) {
    background-color: var(--paper) !important;
    border-color: var(--line) !important;
    color: var(--muted) !important;
  }

  :global(*), :global(*::before), :global(*::after) {
    box-sizing: border-box;
  }

  :global(.border-border), :global(.border-slate-200), :global(.border-gray-200) {
    border-color: var(--line) !important;
  }

  :global(.border) {
    border-width: 2px !important;
    border-style: solid !important;
    border-color: var(--line) !important;
  }

  :global(.border-2) {
    border-width: 2px !important;
    border-style: solid !important;
    border-color: var(--line) !important;
  }

  :global(.border-b) {
    border-bottom-width: 2px !important;
    border-bottom-style: solid !important;
    border-bottom-color: var(--line) !important;
  }

  :global(.border-t) {
    border-top-width: 2px !important;
    border-top-style: solid !important;
    border-top-color: var(--line) !important;
  }

  :global(.border-dashed) {
    border-style: dashed !important;
    border-width: 2px !important;
    border-color: var(--line) !important;
  }

  :global(.card), :global(.dash-section), :global(.auth-card), :global(.course-card), :global(.profile-card), :global(.guard-card), :global(.admin-card), :global(aside.enrollment-panel) {
    border: 2px solid var(--line) !important;
  }

  :global(input), :global(select), :global(textarea) {
    border: 2px solid var(--line) !important;
  }

  :global(a:focus-visible), :global(button:focus-visible), :global(input:focus-visible), :global(select:focus-visible), :global(textarea:focus-visible) {
    outline: 2px solid var(--deep-cyan);
    outline-offset: 2px;
  }

  .skip-link {
    position: absolute;
    top: -3rem;
    inset-inline-start: 1rem;
    background: var(--brand-navy);
    color: #FAF8F5;
    padding: 0.5rem 1rem;
    font-weight: 700;
    z-index: 1000;
    text-decoration: none;
    border: 2px solid var(--brand-accent);
    transition: top 150ms ease;
  }

  .skip-link:focus {
    top: 1rem;
  }

  .layout-container {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
  }

  .global-header {
    background: var(--card);
    border-bottom: 2px solid var(--line);
    position: sticky;
    top: 0;
    z-index: 50;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
  }

  .header-inner {
    max-width: 1200px;
    margin-inline: auto;
    padding: 0.85rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
  }

  .brand {
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    transition: transform 150ms ease, opacity 150ms ease;
  }

  .brand:hover {
    opacity: 0.92;
    transform: translateY(-1px);
  }

  .brand:active {
    transform: translateY(0);
  }

  .brand-logo {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.55rem;
    object-fit: contain;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
  }

  .brand-text {
    color: var(--text-main);
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -0.01em;
    line-height: 1;
  }

  .nav-menu {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  .nav-link {
    color: var(--muted);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    padding: 0.4rem 0.75rem;
    border-radius: 0.375rem;
    transition: color 150ms ease, background-color 150ms ease;
  }

  .nav-link:hover {
    color: var(--storm);
    background-color: var(--paper);
  }

  .nav-link.active {
    color: var(--storm);
    background-color: var(--paper);
    font-weight: 700;
  }

  .header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-inline-start: auto;
  }

  .btn-theme-toggle {
    background: var(--paper);
    color: var(--storm);
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    width: 2.35rem;
    height: 2.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-family: inherit;
    transition: background-color 150ms ease, border-color 150ms ease, transform 120ms ease, color 150ms ease;
  }

  .btn-theme-toggle:hover {
    border-color: var(--deep-cyan);
    transform: translateY(-1px);
    color: var(--deep-cyan);
  }

  .btn-lang-toggle {
    background: var(--paper);
    color: var(--storm);
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    padding: 0 0.75rem;
    height: 2.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 700;
    transition: background-color 150ms ease, border-color 150ms ease, transform 120ms ease, color 150ms ease;
  }

  .btn-lang-toggle:hover {
    border-color: var(--deep-cyan);
    transform: translateY(-1px);
    color: var(--deep-cyan);
  }

  .theme-icon {
    display: block;
    stroke: currentColor;
  }

  .user-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--paper);
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    border: 2px solid var(--line);
    font-size: 0.85rem;
  }

  .user-name {
    font-weight: 700;
    color: var(--storm);
    max-width: 140px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .role-chip {
    font-size: 0.7rem;
    font-weight: 800;
    background: rgba(var(--brand-navy-rgb), 0.08);
    color: var(--brand-navy);
    padding: 0.15rem 0.45rem;
    border-radius: 0.25rem;
    border: 1px solid rgba(var(--brand-navy-rgb), 0.25);
  }

  .btn-logout {
    background: none;
    border: none;
    color: #e53e3e;
    font-size: 0.78rem;
    font-weight: 800;
    cursor: pointer;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-family: inherit;
  }

  .btn-logout:hover {
    background: #fed7d7;
  }

  .btn-auth-login {
    color: var(--storm);
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    padding: 0.4rem 0.85rem;
    border-radius: 0.375rem;
    border: 2px solid var(--line);
    transition: background-color 150ms ease, border-color 150ms ease;
  }

  .btn-auth-login:hover {
    background-color: var(--paper);
    border-color: var(--storm);
  }

  .btn-auth-register {
    background-color: var(--brand-navy);
    color: #FAF8F5;
    font-weight: 800;
    font-size: 0.85rem;
    text-decoration: none;
    padding: 0.45rem 1rem;
    border-radius: 0.375rem;
    border: 2px solid var(--brand-navy);
    transition: transform 150ms ease, opacity 150ms ease, background-color 150ms ease;
  }

  .btn-auth-register:hover {
    transform: translateY(-1px);
    opacity: 0.92;
    background-color: #1E2B4D;
  }

  .main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .main-content:focus {
    outline: none;
  }

  .global-footer {
    background: var(--card);
    border-top: 1px solid var(--line);
    margin-top: auto;
    padding: 0.9rem 1.25rem;
    transition: background-color 150ms ease, border-color 150ms ease;
  }

  .footer-inner {
    max-width: 1200px;
    margin-inline: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
    flex-wrap: wrap;
  }

  .footer-start {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
  }

  .footer-brand {
    text-decoration: none;
    display: inline-flex;
    align-items: baseline;
    gap: 0.25rem;
    transition: transform 150ms ease;
  }

  .footer-brand:hover {
    transform: translateY(-1px);
  }

  .footer-logo-fcai {
    font-size: 0.95rem;
    font-weight: 900;
    color: var(--brand-navy);
    letter-spacing: 0.04em;
  }

  .footer-logo-courses {
    font-size: 0.95rem;
    font-weight: 900;
    color: var(--brand-accent);
    letter-spacing: 0.04em;
  }

  .footer-sep {
    color: var(--line);
    font-size: 0.75rem;
    opacity: 0.7;
  }

  .footer-tagline {
    font-size: 0.78rem;
    color: var(--muted);
    font-weight: 500;
  }

  .footer-links {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
  }

  .footer-links a {
    color: var(--muted);
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none;
    padding: 0.2rem 0.45rem;
    border-radius: 0.35rem;
    transition: color 150ms ease, background-color 150ms ease;
  }

  .footer-links a:hover {
    color: var(--brand-accent);
    background: rgba(var(--brand-accent-rgb), 0.08);
  }

  :global([data-theme='dark']) .footer-links a:hover {
    color: var(--brand-accent);
    background: rgba(var(--brand-accent-rgb), 0.15);
  }

  .separator {
    color: var(--line);
    font-size: 0.5rem;
    opacity: 0.6;
  }

  .footer-copy small {
    color: var(--muted);
    font-size: 0.76rem;
    font-weight: 500;
  }

  .footer-social {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .social-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.15rem;
    height: 2.15rem;
    border-radius: 0.45rem;
    background: var(--paper);
    color: var(--muted);
    border: 1.5px solid var(--line);
    text-decoration: none;
    transition: transform 150ms ease, background-color 150ms ease, border-color 150ms ease, color 150ms ease, box-shadow 150ms ease;
  }

  .social-btn:hover {
    transform: translateY(-2px);
  }

  .social-facebook:hover {
    color: #1877F2;
    border-color: #1877F2;
    background: rgba(24, 119, 242, 0.08);
  }

  .social-youtube:hover {
    color: #FF0000;
    border-color: #FF0000;
    background: rgba(255, 0, 0, 0.08);
  }

  .social-whatsapp:hover {
    color: #25D366;
    border-color: #25D366;
    background: rgba(37, 211, 102, 0.08);
  }

  .social-telegram:hover {
    color: #229ED9;
    border-color: #229ED9;
    background: rgba(34, 158, 217, 0.08);
  }

  :global([data-theme='dark']) .social-facebook:hover {
    background: rgba(24, 119, 242, 0.16);
  }

  :global([data-theme='dark']) .social-youtube:hover {
    background: rgba(255, 0, 0, 0.16);
  }

  :global([data-theme='dark']) .social-whatsapp:hover {
    background: rgba(37, 211, 102, 0.16);
  }

  :global([data-theme='dark']) .social-telegram:hover {
    background: rgba(34, 158, 217, 0.16);
  }

  @media (max-width: 860px) {
    .global-footer {
      padding: 1.15rem 1rem;
    }

    .footer-inner {
      flex-direction: column;
      text-align: center;
      gap: 0.75rem;
      justify-content: center;
    }

    .footer-start {
      justify-content: center;
    }

    .footer-social {
      justify-content: center;
    }

    .footer-links {
      justify-content: center;
    }
  }

  /* Desktop / Mobile navigation visibility */
  .desktop-only {
    display: flex;
  }

  .mobile-only {
    display: none;
  }

  .mobile-header-controls {
    display: none;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-menu-toggle {
    background: var(--paper);
    color: var(--storm);
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    width: 2.35rem;
    height: 2.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-family: inherit;
    transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease, transform 120ms ease;
  }

  .btn-menu-toggle:hover {
    border-color: var(--deep-cyan);
    color: var(--deep-cyan);
  }

  .mobile-drawer {
    background: var(--card);
    border-top: 1px solid var(--line);
    border-bottom: 2px solid var(--line);
    padding: 0.85rem 1.25rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.12);
    animation: drawerFadeIn 160ms cubic-bezier(0.16, 1, 0.3, 1);
  }

  @keyframes drawerFadeIn {
    from {
      opacity: 0;
      transform: translateY(-6px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .mobile-nav-list {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }

  .mobile-nav-link {
    color: var(--storm);
    text-decoration: none;
    font-weight: 700;
    font-size: 0.98rem;
    padding: 0.65rem 0.85rem;
    border-radius: 0.5rem;
    display: block;
    transition: background-color 150ms ease, color 150ms ease;
  }

  .mobile-nav-link:hover,
  .mobile-nav-link.active {
    background: var(--paper);
    color: var(--brand-accent);
  }

  .mobile-drawer-footer {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--line);
  }

  .mobile-user-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--paper);
    padding: 0.65rem 0.85rem;
    border-radius: 0.5rem;
    border: 1px solid var(--line);
    gap: 0.75rem;
  }

  .mobile-user-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    overflow: hidden;
    min-width: 0;
  }

  .mobile-user-info .user-name {
    max-width: 140px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .mobile-logout-btn {
    background: rgba(239, 68, 68, 0.1);
    color: #e53e3e;
    border: 1px solid rgba(239, 68, 68, 0.3);
    padding: 0.35rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.85rem;
    flex-shrink: 0;
  }

  .mobile-auth-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
  }

  .mobile-auth-btn {
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.65rem 0.75rem;
    min-height: 2.6rem;
  }

  .mobile-lang-btn {
    width: 100%;
    height: 2.6rem;
  }

  @media (max-width: 768px) {
    .brand-logo {
      width: 2rem;
      height: 2rem;
      border-radius: 0.45rem;
    }

    .brand-text {
      font-size: 1.15rem;
    }

    .header-inner {
      padding: 0.65rem 1rem;
      gap: 0.75rem;
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
    }

    .desktop-only {
      display: none !important;
    }

    .mobile-only {
      display: flex !important;
    }

    .mobile-drawer.mobile-only {
      display: flex !important;
    }
  }
</style>
