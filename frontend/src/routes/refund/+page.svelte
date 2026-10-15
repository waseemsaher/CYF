<script lang="ts">
  import { onMount } from 'svelte';
  import { getContentBlock } from '$lib/api/admin';
  import { currentLocale } from '$lib/i18n';
  import LegalLayout from '$lib/components/LegalLayout.svelte';

  const fallbackAr = `نظراً لطبيعة الدفع اليدوي عبر محفظة إلكترونية، لا يوجد حالياً نظام استرداد فوري/أوتوماتيكي من داخل حسابك. كل طلب استرداد يُدرس يدوياً من فريق الإدارة.

1. الحالات التي يُقبل فيها الاسترداد
• دفعت مبلغًا بالخطأ (مثل تكرار الدفع لنفس المادة مرتين).
• تم رفض طلب اشتراكك نهائيًا ولم تحصل على الوصول للمادة (المبلغ المدفوع لم يُحسب كاشتراك بالأساس).
• تم إلغاء المادة من قبل المنصة قبل بدء الدراسة.
• طلبت الاسترداد خلال 7 أيام من تاريخ قبول الدفع، وقبل فتح أي محتوى فعلي من المادة أو الانضمام لمجموعة تليجرامها.

2. الحالات التي لا يُقبل فيها الاسترداد
• بعد فتح أي محتوى فعلياً من المادة أو الانضمام لمجموعة التليجرام الخاصة بها.
• بعد انقضاء مدة السبعة أيام من تاريخ القبول.
• إذا تم إلغاء اشتراكك أو إيقافك بسبب مخالفة الشروط والأحكام (مثل مشاركة رابط الانضمام أو رفع إيصال دفع مكرر/مزوّر).

3. طريقة طلب استرداد
راسلنا على info@codeera.tech مرفقاً به رقم عملية الدفع، اسم المادة، وسبب طلب الاسترداد. سيتم مراجعة طلبك يدوياً من قبل الفريق خلال مدة معقولة.

4. طريقة الاسترداد
يتم الاسترداد يدوياً عبر نفس وسيلة الدفع التي استخدمتها (فودافون كاش/إنستاباي/محفظة أخرى)، وقد يختلف الوقت اللازم حسب مزوّد الخدمة.

5. ملاحظة
هذه السياسة لا تنطبق على المواد المجانية، ولا تشمل حالات الخصومات الترويجية المحدودة بمدة (مثل أسعار أسبوع الإطلاق)، التي قد تكون نهائية وغير قابلة للاسترداد حسب ما يُعلن عنها وقت العرض.

6. التواصل
أي استفسار حول استرداد مبلغ، تواصل معنا عبر info@codeera.tech.`;

  const fallbackEn = `Given that payments are processed manually via e-wallet transfer, there is currently no automated self-service refund button in your account. Every refund request is manually reviewed by the administration team.

1. Cases where a refund may be granted
• You paid by mistake (e.g., paying twice for the same course).
• Your enrollment request was rejected and you never gained access to the course or its Telegram group.
• The course was cancelled by the platform before it started.
• Request submitted within 7 days of payment approval, and before opening any actual course content or being added to its Telegram group.

2. Cases where a refund will NOT be granted
• After you have opened any course content or been added to its Telegram group.
• After the 7-day window from approval has passed.
• If your enrollment or membership was terminated for violating the Terms and Conditions (e.g., sharing your join link or re-uploading a fraudulent payment receipt).

3. How to request a refund
Email us at info@codeera.tech with your payment transaction number, the course name, and the reason for your request. Your request will be reviewed manually by the team within a reasonable period.

4. Refund method
Refunds are issued through the same channel you paid with (Vodafone Cash / InstaPay / other wallet). The time it takes may vary depending on the wallet provider.

5. Note
This policy does not apply to free courses, and it does not cover limited-time promotional or discount offers (such as launch-week pricing), which may be non-refundable depending on what is stated at the time of the offer.

6. Contact
For any question about a refund, contact us at info@codeera.tech.`;

  let selectedLang: 'ar' | 'en' | null = $state(null);
  let contentAr: string = $state('');
  let contentEn: string = $state('');
  let loading = $state(true);

  let activeLang = $derived(selectedLang ?? $currentLocale);

  async function loadBlock() {
    try {
      const res = await getContentBlock(fetch, 'legal.refund');
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
  <title>{activeLang === 'en' ? 'Refund Policy | Codeera' : 'سياسة الاسترجاع | منصة Codeera'}</title>
</svelte:head>

<LegalLayout
  activeDoc="refund"
  titleAr="سياسة الاسترجاع"
  titleEn="Refund Policy"
  {contentAr}
  {contentEn}
  {fallbackAr}
  {fallbackEn}
  {loading}
  {selectedLang}
  onSelectLang={(lang) => (selectedLang = lang)}
/>
