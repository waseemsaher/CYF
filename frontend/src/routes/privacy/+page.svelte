<script lang="ts">
  import { onMount } from 'svelte';
  import { getContentBlock } from '$lib/api/admin';
  import { currentLocale } from '$lib/i18n';
  import LegalLayout from '$lib/components/LegalLayout.svelte';

  const fallbackAr = `نحن في Codeera نحرص على حماية بياناتك الشخصية. تشرح هذه السياسة ما البيانات التي نجمعها ولماذا وكيف نستخدمها.

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

  const fallbackEn = `We at Codeera are committed to protecting your personal data. This policy explains what information we collect, why, and how we use it.

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
  let contentAr: string = $state('');
  let contentEn: string = $state('');
  let loading = $state(true);

  let activeLang = $derived(selectedLang ?? $currentLocale);

  async function loadBlock() {
    try {
      const res = await getContentBlock(fetch, 'legal.privacy');
      contentAr = res.data.content?.ar || fallbackAr;
      contentEn = res.data.content?.en || fallbackEn;
    } catch {
      contentAr = fallbackAr;
      contentEn = fallbackEn;
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    loadBlock();
  });
</script>

<svelte:head>
  <title>{activeLang === 'en' ? 'Privacy Policy | Codeera' : 'سياسة الخصوصية | منصة Codeera'}</title>
</svelte:head>

<LegalLayout
  activeDoc="privacy"
  titleAr="سياسة الخصوصية"
  titleEn="Privacy Policy"
  {contentAr}
  {contentEn}
  {fallbackAr}
  {fallbackEn}
  {loading}
  {selectedLang}
  onSelectLang={(lang) => (selectedLang = lang)}
/>
