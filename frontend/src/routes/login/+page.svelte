<script lang="ts">
  import { login } from '$lib/api/auth';
  import { goto } from '$app/navigation';

  let email = $state('');
  let password = $state('');
  let loading = $state(false);
  let errorMessage = $state('');

  async function handleLogin(e: SubmitEvent) {
    e.preventDefault();
    errorMessage = '';

    if (!email || !password) {
      errorMessage = 'يرجى إدخال البريد الإلكتروني وكلمة المرور.';
      return;
    }

    loading = true;

    try {
      const res = await login(fetch, { email, password });
      if (res.data.token) {
        // Redirect to dashboard or payments
        await goto('/courses');
      }
    } catch (err: unknown) {
      if (err instanceof Error) {
        errorMessage = err.message || 'بيانات الدخول غير صحيحة، يرجى المحاولة مرة أخرى.';
      } else {
        errorMessage = 'حدث خطأ أثناء تسجيل الدخول.';
      }
    } finally {
      loading = false;
    }
  }
</script>

<svelte:head>
  <title>تسجيل الدخول | منصة FCAI</title>
  <meta name="description" content="تسجيل الدخول إلى حسابك في منصة دورات كلية الحاسبات والذكاء الاصطناعي." />
</svelte:head>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-header">
      <span class="auth-badge">بوابة الطلاب والمعلمين</span>
      <h1>تسجيل الدخول</h1>
      <p>أدخل بريدك الإلكتروني وكلمة المرور لمتابعة مقرراتك الدراسية.</p>
    </div>

    {#if errorMessage}
      <div class="error-banner" role="alert">
        <span aria-hidden="true">⚠️</span>
        <p>{errorMessage}</p>
      </div>
    {/if}

    <form class="auth-form" onsubmit={handleLogin}>
      <div class="form-group">
        <label for="email">البريد الإلكتروني</label>
        <input
          id="email"
          type="email"
          bind:value={email}
          required
          dir="ltr"
          placeholder="student@example.com"
          autocomplete="email"
        />
      </div>

      <div class="form-group">
        <div class="label-row">
          <label for="password">كلمة المرور</label>
        </div>
        <input
          id="password"
          type="password"
          bind:value={password}
          required
          dir="ltr"
          placeholder="••••••••"
          autocomplete="current-password"
        />
      </div>

      <button type="submit" class="btn-submit" disabled={loading}>
        {#if loading}
          <span>جاري تسجيل الدخول...</span>
        {:else}
          <span>دخول</span>
          <span aria-hidden="true">←</span>
        {/if}
      </button>
    </form>

    <div class="auth-footer">
      <p>
        ليس لديك حساب بعد؟
        <a href="/register">إنشاء حساب طالب جديد</a>
      </p>
    </div>
  </div>
</div>

<style>
  .auth-page {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 3rem 1.25rem 5rem;
    min-height: calc(100vh - 180px);
  }

  .auth-card {
    background: white;
    border: 1px solid var(--line);
    border-radius: 1.25rem;
    padding: clamp(2rem, 5vw, 3rem);
    width: 100%;
    max-width: 460px;
    box-shadow: 0 10px 30px -10px rgba(15, 40, 47, 0.08);
  }

  .auth-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .auth-badge {
    display: inline-block;
    color: var(--deep-cyan);
    font-size: 0.8rem;
    font-weight: 800;
    background: #eef7f6;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    margin-bottom: 0.75rem;
  }

  h1 {
    font-size: 1.85rem;
    font-weight: 900;
    color: var(--storm);
    margin: 0 0 0.5rem;
  }

  .auth-header p {
    color: var(--muted);
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0;
  }

  .error-banner {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: #fff5f5;
    border: 1px solid #feb2b2;
    color: #c53030;
    padding: 0.85rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
  }

  .error-banner p {
    margin: 0;
  }

  .auth-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
  }

  .label-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  label {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--storm);
  }

  input {
    border: 1px solid var(--line);
    border-radius: 0.5rem;
    padding: 0.75rem 0.9rem;
    font-size: 0.95rem;
    font-family: inherit;
    transition: border-color 150ms ease, box-shadow 150ms ease;
  }

  input:focus {
    border-color: var(--deep-cyan);
    outline: none;
    box-shadow: 0 0 0 3px rgba(23, 119, 122, 0.12);
  }

  .btn-submit {
    background: var(--storm);
    color: var(--cyan);
    border: none;
    font-weight: 800;
    font-size: 1.05rem;
    padding: 0.9rem;
    border-radius: 0.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    transition: opacity 150ms ease, transform 150ms ease;
  }

  .btn-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    opacity: 0.95;
  }

  .btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .auth-footer {
    text-align: center;
    border-top: 1px solid var(--line);
    margin-top: 2rem;
    padding-top: 1.25rem;
  }

  .auth-footer p {
    color: var(--muted);
    font-size: 0.9rem;
    margin: 0;
  }

  .auth-footer a {
    color: var(--deep-cyan);
    font-weight: 800;
    text-decoration: none;
    margin-inline-start: 0.25rem;
  }

  .auth-footer a:hover {
    text-decoration: underline;
  }
</style>
