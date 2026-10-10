<script lang="ts">
  import { onMount } from 'svelte';
  import { getAuthToken } from '$lib/api/client';
  import { getCurrentUser } from '$lib/api/auth';
  import AuthGuardCard from '$lib/components/AuthGuardCard.svelte';
  import TeacherManagement from '$lib/components/admin/TeacherManagement.svelte';

  let isUnauthenticated = $state(false);
  let currentRole = $state('');
  let loading = $state(true);

  async function checkAuth() {
    loading = true;
    const token = getAuthToken();
    if (!token) {
      isUnauthenticated = true;
      loading = false;
      return;
    }

    try {
      const userRes = await getCurrentUser(fetch).catch(() => null);
      if (userRes?.data?.user) {
        currentRole = userRes.data.user.role || '';
      }
    } catch {
      isUnauthenticated = true;
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    checkAuth();
  });
</script>

<svelte:head>
  <title>إدارة المحاضرين | لوحة تحكم المسؤول</title>
</svelte:head>

{#if isUnauthenticated || (currentRole && currentRole !== 'admin' && currentRole !== 'superadmin')}
  <AuthGuardCard
    requiredRole="admin"
    {isUnauthenticated}
    {currentRole}
    onRetry={checkAuth}
  />
{:else if loading}
  <div class="admin-loading-shell" dir="rtl">
    <div class="spinner" aria-hidden="true"></div>
    <p>جاري التحقق من صلاحيات المسؤول...</p>
  </div>
{:else}
  <div class="admin-teachers-page" dir="rtl">
    <div class="admin-container">
      <nav class="breadcrumbs" aria-label="مسار التنقل">
        <a href="/">الرئيسية</a>
        <span class="sep">/</span>
        <a href="/admin">لوحة تحكم المسؤول</a>
        <span class="sep">/</span>
        <span class="current">إدارة المحاضرين</span>
      </nav>

      <section class="dash-panel">
        <TeacherManagement />
      </section>
    </div>
  </div>
{/if}

<style>
  .admin-loading-shell {
    min-height: 60vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    color: #e2e8f0;
  }

  .spinner {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(0, 240, 255, 0.2);
    border-top-color: #00f0ff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .admin-teachers-page {
    min-height: 100vh;
    background: #091a1f;
    color: #f1f5f9;
    padding: 2rem 1rem 4rem 1rem;
  }

  .admin-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .breadcrumbs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.6);
  }

  .breadcrumbs a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
  }

  .breadcrumbs a:hover {
    color: #00f0ff;
  }

  .breadcrumbs .sep {
    color: rgba(255, 255, 255, 0.3);
  }

  .breadcrumbs .current {
    color: #00f0ff;
    font-weight: 600;
  }

  .dash-panel {
    background: #0f282f;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 1.75rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  }
</style>
