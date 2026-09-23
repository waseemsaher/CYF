<script lang="ts">
  import type { Snippet } from 'svelte';
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { currentUser, refreshUser, logout } from '$lib/api/auth';

  let { children }: { children: Snippet } = $props();

  onMount(() => {
    refreshUser(fetch);
  });

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

<svelte:head>
  <html lang="ar" dir="rtl"></html>
</svelte:head>

<a href="#main-content" class="skip-link">انتقل إلى المحتوى الرئيسي</a>

<div class="layout-container">
  <header class="global-header">
    <div class="header-inner">
      <a class="brand" href="/" aria-label="الصفحة الرئيسية لمنصة FCAI">
        <span class="brand-fcai">FCAI</span>
        <span class="brand-courses">COURSES</span>
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
      <div class="footer-brand">
        <strong>FCAI COURSES</strong>
        <p>منصة طلاب كلية الحاسبات والذكاء الاصطناعي — جامعة الأزهر</p>
      </div>

      <nav class="footer-links" aria-label="روابط المنصة القانونية">
        <a href="/terms">الشروط والأحكام</a>
        <span class="separator" aria-hidden="true">•</span>
        <a href="/privacy">سياسة الخصوصية</a>
        <span class="separator" aria-hidden="true">•</span>
        <a href="/refund">سياسة الاسترداد</a>
      </nav>

      <div class="footer-copy">
        <small>© 2026 جميع الحقوق محفوظة.</small>
      </div>
    </div>
  </footer>
</div>

<style>
  :global(:root) {
    --storm: #0f282f;
    --cyan: #02eff0;
    --ink: #12343b;
    --muted: #597078;
    --paper: #f3f7f6;
    --line: #bfd6d2;
    --deep-cyan: #17777a;
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
  }

  :global(*), :global(*::before), :global(*::after) {
    box-sizing: border-box;
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
    background: white;
    border-bottom: 2px solid var(--line);
    position: sticky;
    top: 0;
    z-index: 50;
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
    letter-spacing: 0.05em;
  }

  .brand-courses {
    color: var(--deep-cyan);
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.1em;
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
    border: 1.5px solid var(--line);
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
    border: 1.5px solid var(--storm);
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
    background: white;
    border-top: 2px solid var(--line);
    margin-top: auto;
    padding: 2.5rem 1.25rem;
  }

  .footer-inner {
    max-width: 1200px;
    margin-inline: auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
    text-align: center;
  }

  .footer-brand strong {
    color: var(--storm);
    font-size: 1.1rem;
    font-weight: 800;
  }

  .footer-brand p {
    color: var(--muted);
    font-size: 0.85rem;
    margin: 0.25rem 0 0;
  }

  .footer-links {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    justify-content: center;
  }

  .footer-links a {
    color: var(--muted);
    font-size: 0.85rem;
    text-decoration: none;
    transition: color 150ms ease;
  }

  .footer-links a:hover {
    color: var(--deep-cyan);
  }

  .separator {
    color: var(--line);
    font-size: 0.6rem;
  }

  .footer-copy small {
    color: var(--muted);
    font-size: 0.8rem;
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
