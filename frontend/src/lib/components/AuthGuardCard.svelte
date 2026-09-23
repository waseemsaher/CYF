<script lang="ts">
  import { logout } from '$lib/api/auth';

  interface Props {
    requiredRole: 'admin' | 'teacher';
    isUnauthenticated?: boolean;
    currentRole?: string;
    customError?: string;
    onRetry?: () => void;
  }

  let {
    requiredRole,
    isUnauthenticated = false,
    currentRole = '',
    customError = '',
    onRetry,
  }: Props = $props();

  const roleLabels: Record<string, string> = {
    admin: 'المسؤولين',
    teacher: 'أعضاء هيئة التدريس والمحاضرين',
    student: 'طالب',
  };

  const devCredentials = {
    admin: { email: 'admin@example.com', pass: 'password' },
    teacher: { email: 'teacher@example.com', pass: 'password' },
  };

  function handleLogout() {
    logout();
    window.location.href = '/login';
  }
</script>

<div class="auth-guard-container" dir="rtl">
  <div class="guard-card">
    {#if isUnauthenticated}
      <div class="icon-bubble lock-bubble" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
      </div>

      <h2 class="guard-title">تسجيل الدخول مطلوب</h2>
      <p class="guard-desc">
        هذه الصفحة مخصصة لـ
        <strong>{roleLabels[requiredRole] || requiredRole}</strong>
        فقط. يرجى تسجيل الدخول بحساب معتمد للوصول إلى لوحة التحكم.
      </p>

      <div class="guard-actions">
        <a href="/login" class="btn-guard-primary">
          تسجيل الدخول
        </a>
        <a href="/" class="btn-guard-secondary">
          العودة للرئيسية
        </a>
      </div>

      <div class="dev-hint-box">
        <div class="hint-header">
          <span class="hint-dot"></span>
          <span>بيانات الحساب التجريبي للتجربة المحلية:</span>
        </div>
        <div class="hint-credentials">
          <code>البريد: {devCredentials[requiredRole].email}</code>
          <code>كلمة المرور: {devCredentials[requiredRole].pass}</code>
        </div>
      </div>

    {:else if currentRole && currentRole !== requiredRole && currentRole !== 'superadmin'}
      <div class="icon-bubble shield-bubble" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </div>

      <h2 class="guard-title">غير مصرح لك بالوصول</h2>
      <p class="guard-desc">
        أنت مسجل حالياً بحساب
        <strong class="role-highlight">({roleLabels[currentRole] || currentRole})</strong>،
        وهذه اللوحة مخصصة حصرياً لـ
        <strong>{roleLabels[requiredRole] || requiredRole}</strong>.
      </p>

      <div class="guard-actions">
        <a href="/courses" class="btn-guard-primary">
          تصفح المقررات الدراسية
        </a>
        <button type="button" class="btn-guard-secondary" onclick={handleLogout}>
          تبديل الحساب (خروج)
        </button>
      </div>

    {:else}
      <div class="icon-bubble alert-bubble" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </div>

      <h2 class="guard-title">تعذر الوصول إلى اللوحة</h2>
      <p class="guard-desc">
        {customError || 'حدث خطأ أثناء التحقق من الصلاحيات أو تحميل البيانات.'}
      </p>

      <div class="guard-actions">
        {#if onRetry}
          <button type="button" class="btn-guard-primary" onclick={onRetry}>
            إعادة المحاولة
          </button>
        {/if}
        <a href="/" class="btn-guard-secondary">
          العودة للرئيسية
        </a>
      </div>
    {/if}
  </div>
</div>

<style>
  .auth-guard-container {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1rem;
  }

  .guard-card {
    background: #ffffff;
    border: 1px solid #d7e5e3;
    border-radius: 1.25rem;
    box-shadow: 0 10px 30px -10px rgba(15, 40, 47, 0.08);
    max-width: 520px;
    width: 100%;
    padding: 2.5rem 2rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
  }

  .icon-bubble {
    width: 4.5rem;
    height: 4.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .lock-bubble {
    background: #eef8f8;
    color: #17777a;
    border: 2px solid #bce1df;
  }

  .shield-bubble {
    background: #fff8eb;
    color: #d97706;
    border: 2px solid #fed7aa;
  }

  .alert-bubble {
    background: #fef2f2;
    color: #dc2626;
    border: 2px solid #fecaca;
  }

  .guard-title {
    font-size: 1.4rem;
    font-weight: 800;
    color: #0f282f;
    margin: 0;
  }

  .guard-desc {
    font-size: 0.98rem;
    line-height: 1.7;
    color: #597078;
    margin: 0;
    max-width: 440px;
  }

  .role-highlight {
    color: #0f282f;
    font-weight: 700;
  }

  .guard-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    justify-content: center;
    width: 100%;
    margin-top: 0.5rem;
  }

  .btn-guard-primary {
    background: #0f282f;
    color: #ffffff;
    padding: 0.75rem 1.6rem;
    border-radius: 0.6rem;
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: background 150ms ease;
  }

  .btn-guard-primary:hover {
    background: #17777a;
  }

  .btn-guard-secondary {
    background: #f3f7f6;
    color: #0f282f;
    padding: 0.75rem 1.4rem;
    border-radius: 0.6rem;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    border: 1px solid #d7e5e3;
    cursor: pointer;
    transition: background 150ms ease;
  }

  .btn-guard-secondary:hover {
    background: #e2edea;
  }

  .dev-hint-box {
    margin-top: 1rem;
    padding: 0.85rem 1rem;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 0.6rem;
    width: 100%;
    box-sizing: border-box;
    text-align: right;
  }

  .hint-header {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 0.4rem;
  }

  .hint-dot {
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 50%;
    background: #10b981;
  }

  .hint-credentials {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .hint-credentials code {
    font-size: 0.82rem;
    color: #334155;
    background: #ffffff;
    padding: 0.2rem 0.4rem;
    border-radius: 0.3rem;
    border: 1px solid #e2e8f0;
    font-family: monospace;
    direction: ltr;
    display: inline-block;
    text-align: left;
  }
</style>
