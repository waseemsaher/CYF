<script lang="ts">
  import type { Snippet } from 'svelte';
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { currentUser, refreshUser, logout } from '$lib/api/auth';

  let { children }: { children: Snippet } = $props();

  let isDark = $state(false);

  onMount(() => {
    refreshUser(fetch);
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

  const baseLinks = [
    { href: '/', label: 'الرئيسية' },
    { href: '/courses', label: 'الدورات' },
  ];

  let navLinks = $derived.by(() => {
    const links = [...baseLinks];
    const user = $currentUser;
    if (!user) return links;

    const role = user.role || (user.roles && user.roles[0]) || '';

    if (role === 'superadmin' || role === 'admin') {
      links.push({ href: '/admin', label: 'لوحة الإدارة' });
      links.push({ href: '/admin/payments', label: 'طابور المراجعة' });
    } else if (role === 'teacher') {
      links.push({ href: '/teacher', label: 'لوحة المعلم' });
    } else {
      links.push({ href: '/dashboard', label: 'لوحة الطالب' });
      links.push({ href: '/payments', label: 'مدفوعاتي' });
    }

    return links;
  });

  function getRoleLabel(role?: string): string {
    if (role === 'superadmin') return 'مدير عام';
    if (role === 'admin') return 'مسؤول';
    if (role === 'teacher') return 'محاضر';
    return 'طالب';
  }

  function isActive(href: string): boolean {
    if (href === '/') {
      return page.url.pathname === '/';
    }
    return page.url.pathname.startsWith(href);
  }
</script>

<a href="#main-content" class="skip-link">انتقل إلى المحتوى الرئيسي</a>

<div class="layout-container">
  <header class="global-header">
    <div class="header-inner">
      <a class="brand" href="/" aria-label="الصفحة الرئيسية لمنصة Codeera">
        <span class="brand-fcai">CODE</span>
        <span class="brand-courses">ERA</span>
      </a>

      <nav aria-label="التنقل الرئيسي" class="nav-menu">
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

      <div class="header-actions">
        <button
          type="button"
          class="btn-theme-toggle"
          onclick={toggleTheme}
          aria-label={isDark ? 'التبديل إلى الوضع الفاتح' : 'التبديل إلى الوضع الداكن'}
          title={isDark ? 'الوضع الفاتح' : 'الوضع الداكن'}
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

        {#if $currentUser}
          <div class="user-badge">
            <span class="user-name">{$currentUser.name}</span>
            <span class="role-chip">{getRoleLabel($currentUser.role)}</span>
            <button type="button" class="btn-logout" onclick={handleLogout} title="تسجيل الخروج">
              خروج
            </button>
          </div>
        {:else}
          <a href="/login" class="btn-auth-login">دخول</a>
          <a href="/register" class="btn-auth-register">حساب جديد</a>
        {/if}
      </div>
    </div>
  </header>

  <main id="main-content" class="main-content" tabindex="-1">
    {@render children()}
  </main>

  <footer class="global-footer">
    <div class="footer-inner">
      <div class="footer-start">
        <a href="/" class="footer-brand" aria-label="الصفحة الرئيسية لمنصة Codeera">
          <span class="footer-logo-fcai">CODE</span>
          <span class="footer-logo-courses">ERA</span>
        </a>
        <span class="footer-sep" aria-hidden="true">|</span>
        <span class="footer-tagline">منصة Codeera التعليمية</span>
      </div>

      <nav class="footer-links" aria-label="روابط المنصة القانونية">
        <a href="/terms">الشروط والأحكام</a>
        <span class="separator" aria-hidden="true">•</span>
        <a href="/privacy">الخصوصية</a>
        <span class="separator" aria-hidden="true">•</span>
        <a href="/refund">الاسترداد</a>
      </nav>

      <div class="footer-copy">
        <small>© 2026 Codeera. جميع الحقوق محفوظة</small>
      </div>
    </div>
  </footer>
</div>

<style>
  :global(:root) {
    --storm: #0f282f;
    --cyan: #02eff0;
    --ink: #12343b;
    --muted: #476269;
    --paper: #f3f7f6;
    --card: #ffffff;
    --card-hover: #f9fdfc;
    --line: #2d544c;
    --line-subtle: #4b736b;
    --line-bold: #0f282f;
    --deep-cyan: #17777a;
    --border-width: 2px;
  }

  :global(:root[data-theme='dark']),
  :global(html[data-theme='dark']),
  :global(html.dark),
  :global([data-theme='dark']) {
    --storm: #f0fbfb;
    --cyan: #02eff0;
    --ink: #cde3e1;
    --muted: #80a1a8;
    --paper: #081115;
    --card: #0e2026;
    --card-hover: #142a32;
    --line: #1c3d46;
    --line-subtle: #254d58;
    --line-bold: #02eff0;
    --deep-cyan: #0bd5d6;
    --border-width: 2px;
    color-scheme: dark;
  }

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
    background-color: var(--cyan) !important;
    color: #07191d !important;
    border-color: var(--cyan) !important;
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
    box-shadow: 0 0 18px rgba(2, 239, 240, 0.45) !important;
  }

  /* Universal Contrast: Text on Cyan MUST ALWAYS be dark (#07191d) */
  :global(.btn-hero-primary),
  :global(.btn-cta-primary),
  :global(.submit-btn),
  :global([data-theme='dark'] .btn-hero-primary),
  :global([data-theme='dark'] .btn-cta-primary),
  :global([data-theme='dark'] .submit-btn) {
    color: #07191d !important;
  }

  /* Dark mode active tabs */
  :global([data-theme='dark']) .status-tabs button.active,
  :global([data-theme='dark']) .tab-btn.active {
    background-color: var(--cyan) !important;
    color: #07191d !important;
    border-color: var(--cyan) !important;
    font-weight: 800 !important;
  }

  /* Dark mode summary and dark containers */
  :global([data-theme='dark']) .order-summary {
    background: #0d1e24 !important;
    color: #f0fbfb !important;
    border: 2px solid var(--line) !important;
  }

  /* Dark mode badges, avatars & chips */
  :global([data-theme='dark']) .profile-avatar {
    background: rgba(2, 239, 240, 0.15) !important;
    color: var(--cyan) !important;
    border-color: var(--cyan) !important;
  }
  :global([data-theme='dark']) .role-chip {
    background: rgba(2, 239, 240, 0.12) !important;
    color: var(--cyan) !important;
    border: 1px solid rgba(2, 239, 240, 0.3) !important;
  }
  :global([data-theme='dark']) .btn-edit-course,
  :global([data-theme='dark']) .btn-adm-content,
  :global([data-theme='dark']) .btn-link {
    background: rgba(2, 239, 240, 0.1) !important;
    color: var(--cyan) !important;
    border-color: var(--line) !important;
  }
  :global([data-theme='dark']) .section-header {
    background: #0b1a20 !important;
    border-color: var(--line) !important;
  }
  :global([data-theme='dark']) .item-icon {
    background: #142a33 !important;
    color: var(--cyan) !important;
  }
  :global([data-theme='dark']) .item-row:hover {
    background: var(--card-hover) !important;
  }
  :global([data-theme='dark']) .data-table th {
    background: #0b1a20 !important;
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
    background: rgba(0, 136, 204, 0.12) !important;
    border-color: rgba(0, 136, 204, 0.35) !important;
  }
  :global([data-theme='dark']) .deep-link-hint {
    color: #7dd3fc !important;
  }
  :global([data-theme='dark']) .lock-bubble {
    background: rgba(2, 239, 240, 0.1) !important;
    color: var(--cyan) !important;
    border-color: rgba(2, 239, 240, 0.3) !important;
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
    background: rgba(239, 68, 68, 0.12) !important;
    border-color: rgba(239, 68, 68, 0.4) !important;
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
    background: rgba(2, 239, 240, 0.12) !important;
    color: var(--cyan) !important;
    border-color: rgba(2, 239, 240, 0.3) !important;
  }
  :global([data-theme='dark']) .counter-badge {
    background: rgba(2, 239, 240, 0.15) !important;
    color: var(--cyan) !important;
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
    background: rgba(0, 136, 204, 0.2) !important;
    color: #38bdf8 !important;
    border: 1px solid rgba(0, 136, 204, 0.35) !important;
  }
  :global([data-theme='dark']) .role-badge,
  :global([data-theme='dark']) .share-badge {
    background: rgba(2, 239, 240, 0.12) !important;
    color: var(--cyan) !important;
    border-color: rgba(2, 239, 240, 0.3) !important;
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
    background: rgba(2, 239, 240, 0.12) !important;
    border-color: var(--cyan) !important;
    color: var(--cyan) !important;
  }
  :global([data-theme='dark']) .quick-credentials {
    background: #0a171d !important;
    border-color: var(--line) !important;
  }
  :global([data-theme='dark']) .quick-title {
    color: var(--muted) !important;
  }
  :global([data-theme='dark']) .btn-quick {
    background: var(--card) !important;
    color: var(--storm) !important;
    border-color: var(--line) !important;
  }
  :global([data-theme='dark']) .btn-quick:hover {
    background: var(--card-hover) !important;
    border-color: var(--cyan) !important;
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
    background: #0f2127 !important;
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
    background-color: #0b1a20 !important;
    color: var(--storm) !important;
    border-color: var(--line) !important;
  }

  :global([data-theme='dark'] input::placeholder),
  :global([data-theme='dark'] textarea::placeholder) {
    color: #557982 !important;
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
    background: var(--storm);
    color: var(--cyan);
    padding: 0.5rem 1rem;
    font-weight: 700;
    z-index: 1000;
    text-decoration: none;
    border: 2px solid var(--cyan);
    transition: top 150ms ease;
  }

  .skip-link:focus {
    top: 1rem;
  }

  .layout-container {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
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
    align-items: baseline;
    gap: 0.35rem;
  }

  .brand-fcai {
    color: var(--storm);
    font-size: 1.25rem;
    font-weight: 900;
    letter-spacing: 0.04em;
  }

  .brand-courses {
    color: var(--deep-cyan);
    font-size: 1.25rem;
    font-weight: 900;
    letter-spacing: 0.04em;
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
    background: #eef7f6;
    color: var(--deep-cyan);
    padding: 0.15rem 0.45rem;
    border-radius: 0.25rem;
    border: 1px solid rgba(23, 119, 122, 0.25);
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
    background-color: var(--storm);
    color: var(--cyan);
    font-weight: 800;
    font-size: 0.85rem;
    text-decoration: none;
    padding: 0.45rem 1rem;
    border-radius: 0.375rem;
    border: 2px solid var(--storm);
    transition: transform 150ms ease, opacity 150ms ease;
  }

  .btn-auth-register:hover {
    transform: translateY(-1px);
    opacity: 0.95;
  }

  .main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
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
    color: var(--storm);
    letter-spacing: 0.04em;
  }

  .footer-logo-courses {
    font-size: 0.95rem;
    font-weight: 900;
    color: var(--deep-cyan);
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
    color: var(--deep-cyan);
    background: rgba(2, 239, 240, 0.08);
  }

  :global([data-theme='dark']) .footer-links a:hover {
    color: var(--cyan);
    background: rgba(2, 239, 240, 0.12);
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

  @media (max-width: 860px) {
    .global-footer {
      padding: 1.15rem 1rem;
    }

    .footer-inner {
      flex-direction: column;
      text-align: center;
      gap: 0.65rem;
      justify-content: center;
    }

    .footer-start {
      justify-content: center;
    }

    .footer-links {
      justify-content: center;
    }
  }

  @media (max-width: 768px) {
    .header-inner {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .header-actions {
      margin-inline-start: 0;
      width: 100%;
      justify-content: flex-end;
    }
  }
</style>
