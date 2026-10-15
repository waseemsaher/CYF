<script lang="ts">
  import { currentLocale } from '$lib/i18n';

  const contentAr = `باستخدامك منصة Codeera (codeera.tech) فإنك توافق على الشروط التالية. يُرجى قراءتها بعناية قبل التسجيل أو الاشتراك في أي مادة.

1. عن المنصة
Codeera منصة تعليمية إلكترونية موجهة لطلاب كلية الحاسبات والذكاء الاصطناعي (FCAI) بجامعة الأزهر، وتقدم محتوى دراسي عبر مجموعات وقنوات تليجرام مغلقة، مرتبطة بحساب الطالب على المنصة.

2. إنشاء الحساب والأهلية
• يجب تقديم بيانات صحيحة عند التسجيل: الاسم، البريد الإلكتروني، الفرع (بنين/بنات)، الفرقة الدراسية، والقسم.
• أنت مسؤول عن سرية بيانات دخولك، وعن أي نشاط يتم من خلال حسابك.
• لا يجوز إنشاء أكثر من حساب واحد لنفس الشخص، ولا مشاركة الحساب مع أي شخص آخر.

3. الاشتراك في المواد والدفع
• يتم عرض سعر كل مادة بوضوح قبل الاشتراك.
• الدفع حالياً يتم يدوياً عبر فودافون كاش أو إنستاباي أو محفظة إلكترونية أخرى: تقوم بتحويل المبلغ ثم رفع صورة إيصال الدفع من حسابك.
• يقوم فريق الإدارة بمراجعة الإيصال يدوياً والموافقة عليه أو رفضه خلال مدة معقولة. في حالة الرفض، سيظهر لك سبب الرفض ويمكنك إعادة رفع إيصال صحيح.
• إيصال الدفع يجب أن يكون حقيقياً وخاصاً باشتراكك أنت فقط. رفع نفس صورة الإيصال من أكثر من حساب أو محاولة استخدام إيصال شخص آخر يُعد مخالفة صريحة للشروط، ويؤدي لرفض الطلب وقد يؤدي لتعليق الحساب.
• أسعار الخصومات والعروض الترويجية قد تُطبّق تلقائياً على بعض المواد لفترة محددة، وتخضع هذه العروض للتعديل أو الإلغاء دون إشعار مسبق.

4. الوصول للمحتوى عبر تليجرام
• بعد قبول الدفع، يجب ربط حساب تليجرام الخاص بك بحسابك على المنصة للحصول على رابط دعوة شخصي لمجموعة المادة.
• رابط الدعوة شخصي، صالح لمرة واحدة فقط، ولمدة 48 ساعة من إرساله. لا تشارك هذا الرابط مع أي شخص آخر.
• عضويتك في مجموعة المادة مرتبطة باشتراكك الفعّال فقط، وسيتم إزالتك تلقائياً من المجموعة عند انتهاء مدة اشتراكك أو إلغائه.
• محتوى المحاضرات مخصص للاستخدام الشخصي فقط. يُمنع تسجيل الفيديوهات أو المحتوى وإعادة نشره أو توزيعه أو بيعه بأي شكل.

5. مدة الاشتراك والانتهاء
كل اشتراك مرتبط بفصل دراسي محدد (Term)، وينتهي تلقائياً بانتهاء هذا الفصل (مع أي مهلة إضافية تقررها الإدارة إن وُجدت). بعد الانتهاء، يتوقف وصولك للمحتوى ويُزال حسابك من مجموعة تليجرام الخاصة بالمادة.

6. السلوك المحظور
يُحظر: مشاركة روابط الدعوة أو بيانات الدخول، محاولة الاحتيال في إثبات الدفع، إساءة استخدام مجموعات تليجرام، أو أي محاولة للوصول لمحتوى مواد غير مشترك بها. مخالفة ذلك قد تؤدي لتعليق أو إنهاء الحساب دون استرداد، حسب سياسة الاسترداد أدناه.

7. التعديلات على الشروط
قد تُحدَّث هذه الشروط من وقت لآخر. سيتم نشر أي تحديث على هذه الصفحة، واستمرارك في استخدام المنصة بعد التحديث يُعد موافقة عليه.

8. القانون الحاكم والتواصل
تخضع هذه الشروط لقوانين جمهورية مصر العربية. لأي استفسار، تواصل معنا عبر info@codeera.tech.`;

  const contentEn = `By using the Codeera platform (codeera.tech) you agree to the following terms. Please read them carefully before registering or enrolling in any course.

1. About the platform
Codeera is an e-learning platform for Faculty of Computers and Artificial Intelligence (FCAI) students at Al-Azhar University, offering structured courses delivered through closed Telegram groups linked to your account on the platform.

2. Account creation and eligibility
• Accurate information is required at registration: name, email, branch (boys/girls), academic year, and department.
• You are responsible for the security of your account and for any activity carried out through it.
• One account per person. Accounts may not be shared.

3. Enrollment and payment
• Each course's price is shown clearly before you enroll.
• Payment is currently manual, via Vodafone Cash, InstaPay, or another e-wallet: you transfer the amount, then upload a screenshot of the receipt.
• The admin team manually reviews each payment and either approves or rejects it within a reasonable period. If rejected, you'll be told why and can re-upload a correct proof.
• The payment proof must be genuine and specific to your own transaction. Uploading the same receipt image used on another account is treated as a clear violation and may lead to rejection and account suspension.
• Launch-period or seasonal discounts may apply to some courses for a limited time; these are promotional and are not guaranteed to recur.

4. Accessing course content via Telegram
• After payment approval, you must link your own Telegram account on the platform to receive a join link for the course group.
• The join link is personal, single-use, and valid for 48 hours only. It may not be shared with anyone else.
• Your membership in a course's Telegram group is tied strictly to your active enrollment, and you will be automatically removed once your enrollment ends or is revoked.
• Lecture content is for personal use only. Recording, redistributing, or reselling it in any form is prohibited.

5. Enrollment duration and expiry
Each enrollment is tied to a specific academic term and automatically expires at the end of that term (plus any grace period the administration sets). After expiry, your access to the content ends and you are removed from the course's Telegram group.

6. Prohibited conduct
Prohibited: sharing join links or login credentials, attempting to bypass the payment system, abusing Telegram groups, or any attempt to access course material you are not enrolled in. Violations may lead to account suspension or termination, as described in the refund policy.

7. Changes to these terms
These terms may be updated from time to time. Any change will be published on this page, and continuing to use the platform after a change constitutes acceptance of it.

8. Governing law and contact
These terms are governed by the laws of the Arab Republic of Egypt. For any inquiry, contact info@codeera.tech.`;

  let selectedLang: 'ar' | 'en' | null = $state(null);
  let activeLang = $derived(selectedLang ?? $currentLocale);
</script>

<svelte:head>
  <title>{activeLang === 'en' ? 'Terms & Conditions | Codeera' : 'الشروط والأحكام | منصة Codeera'}</title>
</svelte:head>

<div class="legal-page" dir={activeLang === 'ar' ? 'rtl' : 'ltr'}>
  <div class="legal-container">
    <div class="legal-header">
      <div class="legal-header-main">
        <div>
          <h1>{activeLang === 'en' ? 'Terms and Conditions' : 'الشروط والأحكام'}</h1>
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
