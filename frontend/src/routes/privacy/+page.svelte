<script lang="ts">
  import { currentLocale } from '$lib/i18n';

  const contentAr = `نحن في Codeera نحرص على حماية بياناتك الشخصية. تشرح هذه السياسة ما البيانات التي نجمعها ولماذا وكيف نستخدمها.

1. البيانات التي نجمعها
عند التسجيل: الاسم، البريد الإلكتروني، كلمة المرور (مخزّنة بشكل مشفّر)، الفرع الدراسي (بنين/بنات)، الفرقة الدراسية، القسم، واختيارياً: اسم مستخدم تليجرام ورقم الهاتف. عند ربط حساب تليجرام: معرّف حسابك على تليجرام (Telegram User ID) واسم المستخدم العام إن وُجد. عند الاشتراك: صورة إيصال الدفع التي ترفعها واسم/رقم المحوّل. لا نطلب منك أبداً أرقام بطاقاتك البنكية الكاملة أو بيانات دفع مباشرة بطاقاتك ائتمانية.

2. لماذا نستخدم بياناتك
• التحقق من هويتك وصلاحيتك للاشتراك في المواد.
• مراجعة وقبول/رفض مدفوعاتك يدوياً.
• التحقق من أن من يطلب الانضمام لمجموعة التليجرام هو نفس الطالب المشترك (بربط Telegram User ID بالاشتراك الفعّال)، وليس بمراجعة بشرية.
• إيقافك تلقائياً من مجموعات تليجرام عند انتهاء/إلغاء اشتراكك.
• التواصل معك بخصوص دفعاتك أو حسابك.

3. مشاركة البيانات
لا نبيع أو نؤجّر بياناتك لأي جهة خارجية. البيان الوحيد الذي يُشارك مع طرف خارجي هو معرّفك على تليجرام، وذلك فقط لإدارة عضويتك في مجموعات المواد التي اشتركت بها.

4. الاحتفاظ بالبيانات
نحتفظ ببياناتك طالما حسابك نشطًا، ونحتفظ بإيصالات الدفع لفترة معقولة بعد ذلك لغرض الرجوع إليها عند النزاعات أو شكاوى الدفع.

5. إجراءات الحماية
• كلمات المرور مخزّنة بشكل مشفّر، ولا يمكن لأي شخص بمن فيهم فريق العمل رؤيته.
• يتم حذف بيانات EXIF (البيانات الوصفية المخفية) من الصور التي ترفعها كإثبات للدفع.
• نقاط استقبال الطلبات الحساسة (مثل محاولات تسجيل الدخول ورفع المدفوعات) محمي ضد المحاولات المتكررة.
• الوصول لبياناتك محصور على فريق الإدارة المصرّح له فقط، لأغراض مراجعة المدفوعات ودعم الطلاب.

6. حقوقك
يحق لك الاطلاع على بياناتك المخزّنة وتعديلها من خلال صفحة الملف الشخصي. يمكنك كذلك طلب حذف حسابك وبياناتك بالتواصل على info@codeera.tech، مع مراعاة أننا قد نحتفظ ببعض البيانات (مثل سجلات المدفوعات) للمدة اللازمة قانونياً.

7. التواصل
لأي استفسار أو طلب يخص بياناتك، تواصل معنا عبر info@codeera.tech.`;

  const contentEn = `We at Codeera are committed to protecting your personal data. This policy explains what information we collect, why, and how we use it.

1. Information we collect
At registration: name, email, password (stored hashed), branch (boys/girls), academic year, department, and optionally: Telegram username and phone number. When you link your Telegram account: your Telegram user ID and public Telegram username, if set. When you enroll: the payment proof screenshot you upload and the sender name/number. We never ask for full bank card numbers or direct bank credentials.

2. Why we use your data
• Verifying your identity and eligibility to enroll in courses.
• Reviewing and approving/rejecting your payments manually.
• Confirming that whoever requests to join a Telegram group is the same enrolled student (by matching the Telegram user ID to your active enrollment), not a manual review.
• Automatically removing you from Telegram groups when your enrollment ends or is cancelled.
• Contacting you about your payments or account.

3. Data sharing
We do not sell or share your data with any third party. The only data shared with an external party is your Telegram user ID, shared with Telegram itself, solely to manage your membership in the groups you're enrolled in.

4. Data retention
We retain your account data as long as your account is active, and we retain payment receipts for a reasonable period afterward, to be able to refer back to them in case of disputes or payment-related complaints.

5. Security measures
• Passwords are stored hashed; no one at Codeera, including staff, can view your original password.
• EXIF (hidden technical) metadata is stripped from the receipt images you upload.
• Rate limiting is applied to sensitive endpoints (such as login attempts and payment submissions) to protect against automated abuse.
• Access to your data is restricted to authorized administration staff only.

6. Your rights
You have the right to view and update your profile data at any time. You can request deletion of your account and data by contacting info@codeera.tech, noting that we may retain certain data (such as payment records) for the period required by law.

7. Contact
For any question or request regarding your data, contact us at info@codeera.tech.`;

  let selectedLang: 'ar' | 'en' | null = $state(null);
  let activeLang = $derived(selectedLang ?? $currentLocale);
</script>

<svelte:head>
  <title>{activeLang === 'en' ? 'Privacy Policy | Codeera' : 'سياسة الخصوصية | منصة Codeera'}</title>
</svelte:head>

<div class="legal-page" dir={activeLang === 'ar' ? 'rtl' : 'ltr'}>
  <div class="legal-container">
    <div class="legal-header">
      <div class="legal-header-main">
        <div>
          <h1>{activeLang === 'en' ? 'Privacy Policy' : 'سياسة الخصوصية'}</h1>
          <p class="legal-date">{activeLang === 'en' ? 'Last updated: October 2026' : 'آخر تحديث: أكتوبر 2026'}</p>
        </div>
        <div class="lang-pills" role="group" aria-label="Language selection">
          <button
            type="button"
            class="lang-pill"
            class:active={activeLang === 'ar'}
            onclick={() => (selectedLang = 'ar')}
          >
            العربية
          </button>
          <button
            type="button"
            class="lang-pill"
            class:active={activeLang === 'en'}
            onclick={() => (selectedLang = 'en')}
          >
            English
          </button>
        </div>
      </div>
    </div>

    <article class="legal-content">
      {activeLang === 'en' ? contentEn : contentAr}
    </article>
  </div>
</div>

<style>
  .legal-page {
    background-color: var(--paper);
    min-height: calc(100vh - 140px);
    padding: 3rem 1.25rem 5rem;
  }

  .legal-container {
    max-width: 780px;
    margin-inline: auto;
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  .legal-header {
    border-bottom: 2px solid var(--line);
    padding-bottom: 1.5rem;
  }

  .legal-header-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .legal-header h1 {
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0 0 0.35rem;
    letter-spacing: -0.02em;
  }

  .legal-date {
    font-size: 0.82rem;
    color: var(--muted);
    margin: 0;
  }

  .lang-pills {
    display: inline-flex;
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 9999px;
    padding: 0.2rem;
    gap: 0.25rem;
  }

  .lang-pill {
    padding: 0.35rem 0.85rem;
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

  .legal-content {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1rem;
    padding: 2rem;
    font-size: 0.95rem;
    line-height: 1.85;
    color: var(--ink);
    white-space: pre-line;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.04);
  }

  @media (max-width: 600px) {
    .legal-page {
      padding: 2rem 1rem 4rem;
    }
  }
</style>
