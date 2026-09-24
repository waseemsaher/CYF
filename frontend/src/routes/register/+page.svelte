<script lang="ts">
  import type { PageData } from './$types';
  import { register } from '$lib/api/auth';
  import { goto } from '$app/navigation';

  let { data }: { data: PageData } = $props();

  let name = $state('');
  let email = $state('');
  let password = $state('');
  let passwordConfirmation = $state('');
  let branch = $state<'azhar_boys' | 'azhar_girls'>('azhar_boys');
  let academicYear = $state('');
  let department = $state('');
  let telegramUsername = $state('');
  let phone = $state('');

  let loading = $state(false);
  let errorMessage = $state('');

  // Default dropdown selections when loaded
  $effect(() => {
    if (!academicYear && data.academicYears.length > 0) {
      academicYear = data.academicYears[0].name.ar;
    }
    if (!department && data.departments.length > 0) {
      department = data.departments[0].name.ar;
    }
  });

  async function handleRegister(e: SubmitEvent) {
    e.preventDefault();
    errorMessage = '';

    if (password !== passwordConfirmation) {
      errorMessage = 'كلمة المرور وتأكيدها غير متطابقين.';
      return;
    }

    if (password.length < 8) {
      errorMessage = 'يجب ألا تقل كلمة المرور عن 8 أحرف.';
      return;
    }

    loading = true;

    try {
      const res = await register(fetch, {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
        branch,
        academic_year: academicYear,
        department,
        telegram_username: telegramUsername ? telegramUsername.replace(/^@/, '') : undefined,
        phone: phone || undefined,
      });

      if (res.data?.token) {
        window.location.href = '/dashboard';
      }
    } catch (err: unknown) {
      if (err instanceof Error) {
        errorMessage = err.message || 'تعذر إنشاء الحساب، يرجى مراجعة البيانات المدخلة.';
      } else {
        errorMessage = 'حدث خطأ أثناء إنشاء الحساب.';
      }
    } finally {
      loading = false;
    }
  }
</script>

<svelte:head>
  <title>إنشاء حساب طالب جديد | منصة Codeera</title>
  <meta
    name="description"
    content="تسجيل حساب طالب جديد في منصة Codeera التعليمية للبرمجة وعلوم الحاسب."
  />
</svelte:head>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-header">
      <span class="auth-badge">انضم لزملائك</span>
      <h1>إنشاء حساب طالب</h1>
      <p>سجل بياناتك الأكاديمية للوصول إلى مقرراتك وشروحات المناهج واختباراتها.</p>
    </div>

    {#if errorMessage}
      <div class="error-banner" role="alert">
        <span aria-hidden="true">⚠️</span>
        <p>{errorMessage}</p>
      </div>
    {/if}

    <form class="auth-form" onsubmit={handleRegister}>
      <div class="form-group">
        <label for="name">الاسم بالكامل (ثلاثي أو رباعي)</label>
        <input
          id="name"
          type="text"
          bind:value={name}
          required
          placeholder="محمد أحمد علي"
          autocomplete="name"
        />
      </div>

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
        <label for="password">كلمة المرور</label>
        <input
          id="password"
          type="password"
          bind:value={password}
          required
          dir="ltr"
          placeholder="8 أحرف على الأقل"
          autocomplete="new-password"
        />
      </div>

      <div class="form-group">
        <label for="password_confirmation">تأكيد كلمة المرور</label>
        <input
          id="password_confirmation"
          type="password"
          bind:value={passwordConfirmation}
          required
          dir="ltr"
          placeholder="أعد إدخال كلمة المرور"
          autocomplete="new-password"
        />
      </div>

      <div class="form-group">
        <span class="field-label">فرع الكلية</span>
        <div class="radio-toggle" role="radiogroup" aria-label="فرع الكلية">
          <label class="radio-label" class:selected={branch === 'azhar_boys'}>
            <input
              type="radio"
              name="branch"
              value="azhar_boys"
              bind:group={branch}
            />
            <span>بنين (القاهرة)</span>
          </label>
          <label class="radio-label" class:selected={branch === 'azhar_girls'}>
            <input
              type="radio"
              name="branch"
              value="azhar_girls"
              bind:group={branch}
            />
            <span>بنات (القاهرة)</span>
          </label>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="academic_year">السنة الدراسية</label>
          <select id="academic_year" bind:value={academicYear} required>
            {#if data.academicYears.length === 0}
              <option value="السنة الأولى">السنة الأولى</option>
              <option value="السنة الثانية">السنة الثانية</option>
            {:else}
              {#each data.academicYears as year}
                <option value={year.name.ar}>{year.name.ar} ({year.name.en})</option>
              {/each}
            {/if}
          </select>
        </div>

        <div class="form-group">
          <label for="department">القسم الأكاديمي</label>
          <select id="department" bind:value={department} required>
            {#if data.departments.length === 0}
              <option value="علوم الحاسب">علوم الحاسب (CS)</option>
              <option value="الأمن السيبراني">الأمن السيبراني (CY)</option>
              <option value="علم البيانات">علم البيانات (DS)</option>
              <option value="الذكاء الاصطناعي">الذكاء الاصطناعي (AI)</option>
            {:else}
              {#each data.departments as dept}
                <option value={dept.name.ar}>{dept.name.ar} ({dept.name.en})</option>
              {/each}
            {/if}
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="telegram">اسم مستخدم تليجرام (اختياري)</label>
          <input
            id="telegram"
            type="text"
            bind:value={telegramUsername}
            dir="ltr"
            placeholder="@username"
          />
        </div>

        <div class="form-group">
          <label for="phone">رقم الهاتف / واتساب (اختياري)</label>
          <input
            id="phone"
            type="tel"
            bind:value={phone}
            dir="ltr"
            placeholder="01XXXXXXXXX"
          />
        </div>
      </div>

      <button type="submit" class="btn-submit" disabled={loading}>
        {#if loading}
          <span>جاري إنشاء الحساب...</span>
        {:else}
          <span>إنشاء الحساب وبدء التعلم</span>
          <span aria-hidden="true">←</span>
        {/if}
      </button>
    </form>

    <div class="auth-footer">
      <p>
        لديك حساب بالفعل؟
        <a href="/login">تسجيل الدخول</a>
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
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: clamp(1.75rem, 5vw, 2.75rem);
    width: 100%;
    max-width: 620px;
    box-shadow: 0 10px 30px -10px rgba(15, 40, 47, 0.08);
    box-sizing: border-box;
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
    border: 1px solid rgba(23, 119, 122, 0.2);
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
    border: 2px solid #feb2b2;
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

  .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    width: 100%;
    box-sizing: border-box;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
  }

  label, .field-label {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--storm);
  }

  input[type="text"],
  input[type="email"],
  input[type="password"],
  input[type="tel"],
  select {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    padding: 0.75rem 0.9rem;
    font-size: 0.95rem;
    font-family: inherit;
    background: var(--paper);
    color: var(--storm);
    transition: border-color 150ms ease, box-shadow 150ms ease;
  }

  input:focus,
  select:focus {
    border-color: var(--deep-cyan);
    outline: none;
    box-shadow: 0 0 0 3px rgba(23, 119, 122, 0.15);
  }

  .radio-toggle {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
  }

  .radio-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.65rem;
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    cursor: pointer;
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--muted);
    transition: border-color 150ms ease, background 150ms ease, color 150ms ease;
  }

  .radio-label input[type="radio"] {
    margin: 0;
    accent-color: var(--deep-cyan);
  }

  .radio-label.selected {
    border-color: var(--deep-cyan);
    background: #eef7f6;
    color: var(--deep-cyan);
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
    margin-top: 0.75rem;
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

  @media (max-width: 600px) {
    .form-row {
      grid-template-columns: 1fr;
    }
  }
</style>
