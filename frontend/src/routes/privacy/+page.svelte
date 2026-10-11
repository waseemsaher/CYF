<script lang="ts">
  export let data: {
    block?: {
      title?: { ar?: string; en?: string };
      body?: { ar?: string; en?: string };
    } | null;
  };

  const defaultTitle = 'سياسة الخصوصية';
  const defaultBody = `خصوصيتك مهمة جداً لينا في Codeera - كوديرا:
1) البيانات اللي بنطلبها وقت التسجيل (الاسم، الإيميل، رقم التليفون، الفرقة والقسم) هدفها الوحيد تنظيم حسابك وربطك بالمواد المناسبة لفرقتك.
2) صور إيصالات الدفع بتستخدم فقط لمراجعة طلب اشتراكك من إدارة المنصة ومش بيتم مشاركتها مع أي جهة خارجية.
3) تقدر تطلب تعديل بياناتك أو حذف حسابك في أي وقت عن طريق التواصل مع الدعم الفني.`;

  $: title = data?.block?.title?.ar || defaultTitle;
  $: body = data?.block?.body?.ar || defaultBody;
</script>

<svelte:head>
  <title>سياسة الخصوصية | حاسبات الأزهر | Codeera - كوديرا</title>
  <meta
    name="description"
    content="سياسة الخصوصية وحماية بيانات طلاب حاسبات الأزهر (الفرقة الأولى وباقي الفرق) على منصة Codeera - كوديرا."
  />
</svelte:head>

<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8" dir="rtl">
  <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm sm:p-12">
    <h1 class="mb-6 border-b border-slate-100 pb-6 text-2xl font-extrabold text-brand-navy sm:text-3xl">
      {title}
    </h1>
    <div class="prose prose-slate max-w-none leading-loose text-slate-700 whitespace-pre-line">
      {body}
    </div>
  </div>
</div>
