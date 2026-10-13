import { writable, derived } from 'svelte/store';
import {
  BRAND_DISCLAIMER_AR,
  getSafeConfig
} from '$lib/pricing.config';

const SAFE_CONFIG = getSafeConfig();

export type Locale = 'ar' | 'en';

export interface HeroTranslations {
  badge: string;
  titlePrefix: string;
  titleHighlight: string;
  subheading: string;
  description: string;
  typewriterPrefix: string;
  typewriterPhrases: string[];
  primaryCta: string;
  secondaryCta: string;
  dashboardCta: string;
  stats: Array<{ value: string; label: string }>;
  langToggleAria: string;
  langToggleLabel: string;
}

export interface NavTranslations {
  home: string;
  courses: string;
  admin: string;
  reviewQueue: string;
  ctas: string;
  teacher: string;
  dashboard: string;
  payments: string;
  login: string;
  register: string;
  logout: string;
  skipLink: string;
  mainNavAria: string;
  brandAria: string;
  themeLight: string;
  themeDark: string;
  themeToggleAriaLight: string;
  themeToggleAriaDark: string;
  langToggleAria: string;
  langToggleLabel: string;
  menuToggleAria: string;
  menuCloseAria: string;
  roles: {
    superadmin: string;
    admin: string;
    teacher: string;
    student: string;
  };
  footer: {
    tagline: string;
    disclaimer: string;
    socialAria: string;
    terms: string;
    privacy: string;
    refund: string;
    rights: string;
    legalNavAria: string;
  };
}

export interface HomeTranslations {
  metaTitle: string;
  metaDesc: string;
  steps: {
    eyebrow: string;
    title: string;
    subtitle: string;
    step1Title: string;
    step1Desc: string;
    step2Title: string;
    step2Desc: string;
    step3Title: string;
    step3Desc: string;
  };
  courses: {
    eyebrow: string;
    title: string;
    viewAll: string;
    priceCaption: string;
    onSale: string;
    details: string;
    currency: string;
    emptyNotice: string;
  };
  features: {
    eyebrow: string;
    title: string;
    subtitle: string;
    items: Array<{ title: string; desc: string }>;
  };
  faq: {
    eyebrow: string;
    title: string;
    subtitle: string;
    items: Array<{ q: string; a: string }>;
  };
  cta: {
    badge: string;
    title: string;
    desc: string;
    primary: string;
    secondary: string;
  };
}

export interface CoursesTranslations {
  metaTitle: string;
  metaDesc: string;
  title: string;
  subtitle: string;
  desc: string;
  adminTitle: string;
  adminDesc: string;
  addCourse: string;
  searchPlaceholder: string;
  searchAria: string;
  filterSectionAria: string;
  filterFormAria: string;
  yearLabel: string;
  allYears: string;
  generalForAll: string;
  deptLabel: string;
  allDepts: string;
  applyFilter: string;
  resetFilter: string;
  priceCaption: string;
  currency: string;
  exploreDetails: string;
  onSale: string;
  emptyTitle: string;
  emptyDesc: string;
  clearFilters: string;
}

export interface AuthTranslations {
  login: {
    metaTitle: string;
    metaDesc: string;
    badge: string;
    title: string;
    subtitle: string;
    emailLabel: string;
    emailPlaceholder: string;
    passwordLabel: string;
    passwordPlaceholder: string;
    forgotPasswordLink: string;
    submit: string;
    submitting: string;
    noAccount: string;
    registerLink: string;
    alreadyLoggedInTitle: string;
    alreadyLoggedInWelcome: string;
    goToDashboard: string;
    browseCourses: string;
    errors: {
      required: string;
      invalid: string;
      generic: string;
    };
  };
  forgotPassword: {
    metaTitle: string;
    metaDesc: string;
    badge: string;
    title: string;
    subtitle: string;
    emailLabel: string;
    emailPlaceholder: string;
    submit: string;
    submitting: string;
    backToLogin: string;
    successTitle: string;
    successMessage: string;
    resendNote: string;
    errors: {
      required: string;
      invalidEmail: string;
      throttled: string;
      generic: string;
    };
  };
  resetPassword: {
    metaTitle: string;
    metaDesc: string;
    badge: string;
    title: string;
    subtitle: string;
    emailLabel: string;
    passwordLabel: string;
    passwordPlaceholder: string;
    confirmPasswordLabel: string;
    confirmPasswordPlaceholder: string;
    submit: string;
    submitting: string;
    backToLogin: string;
    requestNewLink: string;
    successTitle: string;
    successMessage: string;
    redirecting: string;
    invalidLinkTitle: string;
    invalidLinkMessage: string;
    errors: {
      required: string;
      passwordMin: string;
      passwordMismatch: string;
      tokenInvalid: string;
      throttled: string;
      generic: string;
    };
  };
  register: {
    metaTitle: string;
    metaDesc: string;
    badge: string;
    title: string;
    subtitle: string;
    nameLabel: string;
    namePlaceholder: string;
    emailLabel: string;
    emailPlaceholder: string;
    passwordLabel: string;
    passwordPlaceholder: string;
    confirmPasswordLabel: string;
    confirmPasswordPlaceholder: string;
    branchLabel: string;
    branchBoys: string;
    branchGirls: string;
    academicYearLabel: string;
    departmentLabel: string;
    telegramLabel: string;
    telegramPlaceholder: string;
    phoneLabel: string;
    phonePlaceholder: string;
    submit: string;
    submitting: string;
    hasAccount: string;
    loginLink: string;
    years: {
      first: string;
      second: string;
    };
    departments: {
      cs: string;
      cy: string;
      ds: string;
      ai: string;
    };
    errors: {
      passwordMismatch: string;
      passwordMinLength: string;
      failed: string;
      generic: string;
    };
  };
  guard: {
    loginRequiredTitle: string;
    loginRequiredDescPrefix: string;
    loginRequiredDescSuffix: string;
    accessDeniedTitle: string;
    accessDeniedDescCurrent: string;
    accessDeniedDescRequired: string;
    cannotAccessTitle: string;
    defaultError: string;
    loginBtn: string;
    homeBtn: string;
    browseCoursesBtn: string;
    switchAccountBtn: string;
    retryBtn: string;
    roles: {
      admin: string;
      teacher: string;
      student: string;
      superadmin: string;
    };
  };
}

export interface Translations {
  hero: HeroTranslations;
  nav: NavTranslations;
  home: HomeTranslations;
  courses: CoursesTranslations;
  auth: AuthTranslations;
}

export const translations: Record<Locale, Translations> = {
  ar: {
    hero: {
      badge: 'Codeera - كوديرا | لطلاب حاسبات وذكاء اصطناعي الأزهر',
      titlePrefix: 'مواد حاسبات الأزهر مترتبة حسب فرقتك وقسمك،',
      titleHighlight: 'من الفيديو للكويز في مكان واحد.',
      subheading:
        'اختار مادتك، حول الاشتراك بفودافون كاش أو إنستاباي وارفع لقطة شاشة للإيصال، وهنراجعه يدوي ونفتحلك الشرح والكويزات وجروب المادة.',
      description:
        'منصة Codeera - كوديرا بتجمعلك مواد كلية الحاسبات والذكاء الاصطناعي جامعة الأزهر بنظام واضح: محاضرات مترتبة، كويزات تقيس مستواك مع مراجعة الإجابات الصحيحة، وتقدر تسأل الفريق على واتساب أو في جروب المادة.',
      typewriterPrefix: 'أقسام ومواد الكلية:',
      typewriterPhrases: [
        'مواد الفرقة الأولى الأساسية',
        'علوم الحاسب والبرمجة (CS)',
        'الذكاء الاصطناعي وعلم البيانات (AI & DS)',
        'الأمن السيبراني والشبكات (CY)'
      ],
      primaryCta: 'ابدأ بمادة واحدة',
      secondaryCta: 'شوف الدرس التجريبي',
      dashboardCta: 'لوحة الطالب',
      stats: [
        { value: 'فرقة أولى والباقي جاي', label: 'مواد متاحة حالياً على المنصة' },
        { value: SAFE_CONFIG.reviewTimeText, label: 'وقت مراجعة الإيصال وتفعيل المقرر' },
        { value: 'مبني على توصيف مقرر فرقتك', label: 'محتوى مترتب حسب منهج الكلية' }
      ],
      langToggleAria: 'Switch to English',
      langToggleLabel: 'English'
    },
    nav: {
      home: 'الرئيسية',
      courses: 'المواد الدراسية',
      admin: 'لوحة الإدارة',
      reviewQueue: 'مراجعة الإيصالات',
      ctas: 'إدارة أزرار CTA',
      teacher: 'لوحة المحاضر',
      dashboard: 'مقرراتي',
      payments: 'مدفوعاتي',
      login: 'دخول',
      register: 'حساب جديد',
      logout: 'خروج',
      skipLink: 'انتقل إلى المحتوى الرئيسي',
      mainNavAria: 'التنقل الرئيسي',
      brandAria: 'الصفحة الرئيسية لمنصة Codeera - كوديرا',
      themeLight: 'الوضع الفاتح',
      themeDark: 'الوضع الداكن',
      themeToggleAriaLight: 'التبديل إلى الوضع الفاتح',
      themeToggleAriaDark: 'التبديل إلى الوضع الداكن',
      langToggleAria: 'Switch to English',
      langToggleLabel: 'English',
      menuToggleAria: 'فتح القائمة الرئيسية',
      menuCloseAria: 'إغلاق القائمة الرئيسية',
      roles: {
        superadmin: 'مدير عام',
        admin: 'مسؤول',
        teacher: 'محاضر',
        student: 'طالب'
      },
      footer: {
        tagline: 'Codeera - كوديرا | شرح وتدريب لطلاب حاسبات الأزهر',
        disclaimer: BRAND_DISCLAIMER_AR,
        socialAria: 'حسابات التواصل الاجتماعي لمنصة Codeera - كوديرا',
        terms: 'الشروط والأحكام',
        privacy: 'سياسة الخصوصية',
        refund: 'سياسة الاسترداد',
        rights: 'جميع الحقوق محفوظة لمنصة Codeera - كوديرا',
        legalNavAria: 'روابط السياسات والشروط'
      }
    },
    home: {
      metaTitle: 'كورسات حاسبات وذكاء اصطناعي الأزهر | فرقة أولى | Codeera - كوديرا',
      metaDesc:
        'منصة Codeera - كوديرا لطلاب كلية الحاسبات والذكاء الاصطناعي جامعة الأزهر (فرقة أولى وجميع الأقسام). شروحات مترتبة، كويزات تفاعلية، ودفع بفودافون كاش أو إنستاباي.',
      steps: {
        eyebrow: 'طريقة الاشتراك',
        title: 'إزاي تبدأ تذاكر على Codeera - كوديرا؟',
        subtitle:
          'خطوات واضحة من لحظة اختيار المادة لحد ما يفتحلك المحتوى بعد مراجعة إيصال الدفع.',
        step1Title: 'اختار مادتك حسب فرقتك وقسمك',
        step1Desc:
          'ادخل على صفحة المواد، شوف المنهج المتقسم فصول ودروس، وتأكد من تفاصيل المقرر قبل ما تشترك.',
        step2Title: 'ادفع بفودافون كاش أو إنستاباي وارفع الإيصال',
        step2Desc:
          'بتدفع على رقم رسمي عبر فودافون كاش أو إنستاباي، وبترفع صورة إيصال التحويل من موبايلك في ثواني.',
        step3Title: 'مراجعة يدوية للإيصال وتفعيل المقرر',
        step3Desc: `فريق الإدارة بيراجع إيصال الدفع يدوياً للتأكد من التحويل (${SAFE_CONFIG.reviewTimeText})، وبعد الاعتماد بيفتحلك المحتوى بالكامل وبيوصلك تأكيد.`
      },
      courses: {
        eyebrow: 'مواد الكلية',
        title: 'المقررات المتاحة لفرقتك',
        viewAll: 'شوف كل المواد المتاحة',
        priceCaption: 'اشتراك الترم للمقرر',
        onSale: 'خصم حالي',
        details: 'تفاصيل المقرر',
        currency: 'ج.م',
        emptyNotice: 'جاري تجهيز المواد الدراسية للفرقة، وهتظهر هنا أول ما تتاح للتسجيل.'
      },
      features: {
        eyebrow: 'إيه اللي هتلاقيه جوه المنصة؟',
        title: 'كل اللي محتاجه عشان تذاكر مادتك من غير تشتت',
        subtitle:
          'بدل ما تدور في مصادر كتير متفرقة، جمعنالك الشرح والتدريب والمتابعة في مكان واحد واضح.',
        items: [
          {
            title: 'مترتبة حسب فرقتك وقسمك',
            desc: 'المحتوى مبني على توصيف مقرر فرقتك في حاسبات الأزهر، ومتقسم فصول وموضوعات بنفس تسلسل الدراسة.'
          },
          {
            title: 'كويزات تقيس مستواك بعد كل جزء',
            desc: 'بتحل كويزات تفاعلية على الفصول بتوريك درجتك والإجابات الصحيحة مع توضيح الحل.'
          },
          {
            title: 'تقدر تسأل الفريق على واتساب أو في جروب المادة',
            desc: 'لو وقفت قدامك نقطة في الشرح أو المسائل، تقدر تسأل الفريق على واتساب أو في جروب المادة خلال فترة الدراسة.'
          },
          {
            title: 'بتدفع على رقم رسمي وبتستلم تأكيد',
            desc: 'الدفع متاح عبر فودافون كاش أو إنستاباي، ولو الإيصال اترفض لأي سبب حقك محفوظ وفلوسك بترجعلك.'
          }
        ]
      },
      faq: {
        eyebrow: 'الأسئلة الشائعة',
        title: 'إجابات صريحة على كل أسئلتك قبل الاشتراك',
        subtitle:
          'كل التفاصيل عن طريقة الدفع، وقت المراجعة، تشغيل الفيديوهات، وسياسة الاسترداد.',
        items: [
          {
            q: 'إيه هي طرق الدفع المتاحة للاشتراك في المواد؟',
            a: 'تقدر تدفع بسهولة عن طريق فودافون كاش (Vodafone Cash) أو إنستاباي (InstaPay) أو أي محفظة إلكترونية في مصر. بتحول المبلغ على الرقم الرسمي الموضح في صفحة الدفع، وبعدها بترفع لقطة شاشة (Screenshot) للإيصال.'
          },
          {
            q: 'إيصال الدفع بياخد وقت قد إيه عشان يتراجع ويتفعل الكورس؟',
            a: `مراجعة الإيصالات بتتم بشكل يدوي من فريق الإدارة عشان نضمن دقة كل عملية دفع (${SAFE_CONFIG.reviewTimeText}). أول ما الإيصال يتعتمد، المقرر بيفتح في حسابك وبتوصلك رسالة تأكيد.`
          },
          {
            q: 'اشتراك المادة بيفضل شغال معايا لحد إمتى؟',
            a: `اشتراكك في أي مقرر بيفضل متاح معاك ${SAFE_CONFIG.subscriptionValidityText}، عشان تقدر تذاكر وتراجع براحتك ليلة الامتحان.`
          },
          {
            q: 'إيه هي سياسة استرداد الرسوم لو غيرت رأيي أو حصلت مشكلة؟',
            a: `لو إيصال الدفع اترفض أو حصل خطأ في التحويل، المبلغ بيرجعلك بالكامل. وكمان خلال ${SAFE_CONFIG.refundWindowDays} أيام من تاريخ تفعيل المقرر، تقدر تطلب استرداد رسوم الاشتراك من غير أي شرط على نسبة المشاهدة عن طريق التواصل معانا على واتساب، وبيتم رد المبلغ خلال ${SAFE_CONFIG.refundProcessingDaysText}. ${SAFE_CONFIG.refundMisuseClauseAr}`
          },
          {
            q: 'إيه المتطلبات التقنية عشان أشوف فيديوهات الشرح؟',
            a: 'فيديوهات الشرح بتفتح من خلال رابط تليجرام مخصص للمشتركين، وعشان كدة هتحتاج يكون عندك حساب تليجرام مربوط بحسابك على المنصة.'
          },
          {
            q: 'إزاي أتواصل مع الدعم الفني لو عندي سؤال قبل أو بعد الاشتراك؟',
            a: 'تقدر تبعتلنا في أي وقت على واتساب الدعم الفني الموجود في أسفل الموقع وفي قسم الثقة، وهنرد عليك في أسرع وقت لمساعدتك في أي خطوة.'
          },
          {
            q: 'مين اللي ورا منصة Codeera - كوديرا؟ وهل هي تابعة للجامعة؟',
            a: 'منصة Codeera - كوديرا بناها مبرمج زميل ليكم بهدف تنظيم شرح مواد حاسبات الأزهر بطريقة عملية وبسيطة. المنصة مستقلة تماماً وغير تابعة رسمياً لجامعة الأزهر.'
          }
        ]
      },
      cta: {
        badge: 'Codeera - كوديرا',
        title: 'جاهز تبدأ مذاكرة بنظام واضح؟',
        desc: 'اختار مادتك، ادفع بفودافون كاش أو إنستاباي على رقم رسمي، وهنراجع الإيصال ونفتحلك المحتوى.',
        primary: 'شوف مواد فرقتك',
        secondary: 'تواصل معنا على واتساب'
      }
    },
    courses: {
      metaTitle: 'مواد كلية حاسبات وذكاء اصطناعي الأزهر | فرقة أولى | Codeera - كوديرا',
      metaDesc:
        'تصفح كورسات ومواد كلية الحاسبات والذكاء الاصطناعي جامعة الأزهر (فرقة أولى والأقسام المتاحة) على منصة Codeera - كوديرا. محتوى مبني على توصيف مقرر فرقتك مع كويزات ودعم.',
      title: 'مواد حاسبات وذكاء اصطناعي الأزهر',
      subtitle: 'اختار فرقتك وقسمك وشوف المقررات المتاحة للشرح والتدريب على Codeera - كوديرا.',
      desc: 'محتوى مبني على توصيف مقرر فرقتك، متقسم محاضرات وملفات وكويزات تقيس مستواك، مع مراجعة واضحة للإيصالات وضمان استرداد.',
      adminTitle: 'صلاحيات إدارة المقررات',
      adminDesc: 'يمكنك إنشاء مقررات جديدة أو تعديل المحتوى والأسعار مباشرة من هنا.',
      addCourse: 'إضافة مقرر دراسي جديد',
      searchPlaceholder: 'ابحث باسم المادة أو الكود (مثال: C++، رياضيات متقطعة، فيزياء)...',
      searchAria: 'البحث في المقررات',
      filterSectionAria: 'أدوات البحث والتصفية',
      filterFormAria: 'تصفية المواد حسب الفرقة والقسم',
      yearLabel: 'الفرقة الدراسية:',
      allYears: 'كل الفرق الدراسية',
      generalForAll: 'عام لكل الفرق والأقسام',
      deptLabel: 'القسم الأكاديمي:',
      allDepts: 'كل الأقسام',
      applyFilter: 'عرض المواد',
      resetFilter: 'مسح الفلتر',
      priceCaption: 'اشتراك الترم',
      currency: 'جنيه',
      exploreDetails: 'تفاصيل المقرر والاشتراك',
      onSale: 'خصم حالي',
      emptyTitle: 'مفيش مواد مطابقة لبحثك حالياً.',
      emptyDesc: 'جرب تغير كلمة البحث أو تختار فرقة أو قسم تاني عشان تشوف المواد المتاحة.',
      clearFilters: 'عرض كل المواد'
    },
    auth: {
      login: {
        metaTitle: 'تسجيل الدخول | Codeera - كوديرا',
        metaDesc: 'سجل دخولك لحسابك على منصة Codeera - كوديرا لمتابعة موادك في حاسبات الأزهر.',
        badge: 'حسابك على Codeera - كوديرا',
        title: 'تسجيل الدخول',
        subtitle: 'اكتب بريدك الإلكتروني وكلمة السر عشان تكمل مذاكرة موادك.',
        emailLabel: 'البريد الإلكتروني',
        emailPlaceholder: 'name@example.com',
        passwordLabel: 'كلمة المرور',
        passwordPlaceholder: '••••••••',
        forgotPasswordLink: 'نسيت كلمة السر؟',
        submit: 'دخول',
        submitting: 'جاري الدخول...',
        noAccount: 'لسه معندكش حساب؟',
        registerLink: 'اعمل حساب طالب جديد',
        alreadyLoggedInTitle: 'أنت مسجل دخول بالفعل',
        alreadyLoggedInWelcome: 'أهلاً بيك يا',
        goToDashboard: 'ادخل على لوحة الطالب',
        browseCourses: 'تصفح المواد الدراسية',
        errors: {
          required: 'من فضلك اكتب البريد الإلكتروني وكلمة المرور.',
          invalid: 'بيانات الدخول مش مظبوطة، راجع الإيميل وكلمة السر وجرب تاني.',
          generic: 'حصلت مشكلة أثناء تسجيل الدخول، جرب تاني.'
        }
      },
      forgotPassword: {
        metaTitle: 'استعادة كلمة المرور | Codeera - كوديرا',
        metaDesc: 'استعادة وتعيين كلمة المرور لحسابك في منصة Codeera - كوديرا.',
        badge: 'استعادة الحساب',
        title: 'نسيت كلمة المرور؟',
        subtitle: 'اكتب بريدك الإلكتروني وهنبعتلك رابط تعمل بيه كلمة سر جديدة.',
        emailLabel: 'البريد الإلكتروني',
        emailPlaceholder: 'name@example.com',
        submit: 'إرسال رابط الاستعادة',
        submitting: 'جاري الإرسال...',
        backToLogin: 'الرجوع لتسجيل الدخول',
        successTitle: 'بعتنالك الرابط',
        successMessage:
          'لو الإيميل ده مسجل عندنا، هتوصلك رسالة فيها رابط تغيير كلمة السر خلال دقايق.',
        resendNote: 'ملقيتش الرسالة؟ بص في مجلد الرسائل غير المرغوب فيها (Spam) أو جرب تاني بعد شوية.',
        errors: {
          required: 'من فضلك اكتب البريد الإلكتروني.',
          invalidEmail: 'من فضلك اكتب بريد إلكتروني صحيح.',
          throttled: 'حاولت مرات كتير في وقت قصير. استنى شوية وجرب تاني.',
          generic: 'حصل خطأ أثناء إرسال الرابط، جرب تاني.'
        }
      },
      resetPassword: {
        metaTitle: 'تعيين كلمة مرور جديدة | Codeera - كوديرا',
        metaDesc: 'تعيين كلمة مرور جديدة لحسابك في منصة Codeera - كوديرا.',
        badge: 'تأمين الحساب',
        title: 'تعيين كلمة مرور جديدة',
        subtitle: 'اكتب كلمة المرور الجديدة وأكدها عشان تقدر تدخل لحسابك.',
        emailLabel: 'البريد الإلكتروني',
        passwordLabel: 'كلمة المرور الجديدة',
        passwordPlaceholder: '8 حروف أو أرقام على الأقل',
        confirmPasswordLabel: 'تأكيد كلمة المرور الجديدة',
        confirmPasswordPlaceholder: 'اكتب كلمة المرور مرة تانية',
        submit: 'حفظ كلمة المرور',
        submitting: 'جاري الحفظ...',
        backToLogin: 'الرجوع لتسجيل الدخول',
        requestNewLink: 'اطلب رابط جديد',
        successTitle: 'تم تغيير كلمة المرور',
        successMessage: 'حدثنا كلمة السر لحسابك. تقدر دلوقتي تسجل دخولك بيها.',
        redirecting: 'جاري تحويلك لصفحة الدخول...',
        invalidLinkTitle: 'الرابط غير صالح أو انتهت مدته',
        invalidLinkMessage:
          'الرابط ده منتهي (مدته 60 دقيقة بس) أو اتستخدم قبل كدة. تقدر تطلب رابط جديد في أي وقت.',
        errors: {
          required: 'من فضلك املأ كل الحقول المطلوبة.',
          passwordMin: 'كلمة المرور لازم تكون 8 حروف أو أرقام على الأقل.',
          passwordMismatch: 'كلمتين المرور مش متطابقين.',
          tokenInvalid: 'رابط التعيين غير صالح أو منتهي. اطلب رابط جديد.',
          throttled: 'حاولت مرات كتير، استنى شوية وجرب تاني.',
          generic: 'حصل خطأ أثناء حفظ كلمة المرور.'
        }
      },
      register: {
        metaTitle: 'إنشاء حساب طالب | Codeera - كوديرا',
        metaDesc: 'اعمل حساب طالب على منصة Codeera - كوديرا لطلاب حاسبات وذكاء اصطناعي الأزهر.',
        badge: 'حساب طالب جديد',
        title: 'إنشاء حساب على Codeera - كوديرا',
        subtitle: 'سجل بياناتك واختار فرقتك وقسمك عشان تشوف المواد المناسبة ليك.',
        nameLabel: 'الاسم ثلاثي أو رباعي',
        namePlaceholder: 'محمد أحمد علي',
        emailLabel: 'البريد الإلكتروني',
        emailPlaceholder: 'name@example.com',
        passwordLabel: 'كلمة المرور',
        passwordPlaceholder: '8 حروف أو أرقام على الأقل',
        confirmPasswordLabel: 'تأكيد كلمة المرور',
        confirmPasswordPlaceholder: 'اكتب كلمة المرور مرة تانية',
        branchLabel: 'فرع الكلية',
        branchBoys: 'بنين (القاهرة)',
        branchGirls: 'بنات (القاهرة)',
        academicYearLabel: 'الفرقة الدراسية',
        departmentLabel: 'القسم الأكاديمي',
        telegramLabel: 'اسم مستخدم تليجرام (اختياري)',
        telegramPlaceholder: '@username',
        phoneLabel: 'رقم الموبايل (فودافون كاش / إنستاباي)',
        phonePlaceholder: '01012345678',
        submit: 'إنشاء الحساب والمتابعة',
        submitting: 'جاري إنشاء الحساب...',
        hasAccount: 'عندك حساب بالفعل؟',
        loginLink: 'سجل دخولك',
        years: {
          first: 'الفرقة الأولى',
          second: 'الفرقة الثانية'
        },
        departments: {
          cs: 'علوم الحاسب (CS)',
          cy: 'الأمن السيبراني (CY)',
          ds: 'علم البيانات (DS)',
          ai: 'الذكاء الاصطناعي (AI)'
        },
        errors: {
          passwordMismatch: 'كلمة المرور وتأكيدها مش متطابقين.',
          passwordMinLength: 'كلمة المرور لازم متقلش عن 8 حروف أو أرقام.',
          failed: 'مقدرناش ننشئ الحساب، راجع البيانات المكتوبة وجرب تاني.',
          generic: 'حصل خطأ أثناء إنشاء الحساب.'
        }
      },
      guard: {
        loginRequiredTitle: 'تسجيل الدخول مطلوب',
        loginRequiredDescPrefix: 'الصفحة دي مخصصة لـ',
        loginRequiredDescSuffix: 'بس. سجل دخولك الأول عشان تقدر تكمل.',
        accessDeniedTitle: 'غير مصرح بالوصول',
        accessDeniedDescCurrent: 'أنت مسجل حالياً بحساب',
        accessDeniedDescRequired: 'والصفحة دي مخصصة لـ',
        cannotAccessTitle: 'مقدرناش نفتح الصفحة',
        defaultError: 'حصلت مشكلة أثناء التحقق من الصلاحيات أو تحميل البيانات.',
        loginBtn: 'تسجيل الدخول',
        homeBtn: 'الرجوع للرئيسية',
        browseCoursesBtn: 'تصفح المواد الدراسية',
        switchAccountBtn: 'تبديل الحساب (خروج)',
        retryBtn: 'حاول مرة تانية',
        roles: {
          admin: 'المسؤولين',
          teacher: 'المحاضرين',
          student: 'الطلاب المسجلين',
          superadmin: 'المدير العام'
        }
      }
    }
  },
  en: {
    hero: {
      badge: 'Codeera - كوديرا | For Al-Azhar FCAI Students',
      titlePrefix: 'Al-Azhar Computing & AI courses organized by your year and department,',
      titleHighlight: 'from video lessons to quizzes in one place.',
      subheading:
        'Pick your course, pay via Vodafone Cash or InstaPay to our official number and upload the receipt screenshot — we review it manually and unlock your lessons, quizzes, and course group access.',
      description:
        'Codeera organizes coursework for Faculty of Computers & AI students at Al-Azhar University with structured video lectures, quizzes with answer reviews, and direct support via WhatsApp or the course group.',
      typewriterPrefix: 'Academic Tracks:',
      typewriterPhrases: [
        'First Year Core Courses',
        'Computer Science & Programming (CS)',
        'Artificial Intelligence & Data Science (AI & DS)',
        'Cybersecurity & Networks (CY)'
      ],
      primaryCta: 'Start With One Course',
      secondaryCta: 'Watch Sample Lesson',
      dashboardCta: 'My Dashboard',
      stats: [
        { value: '1st Year & More Coming', label: 'Available Courses in Catalog' },
        { value: SAFE_CONFIG.reviewTimeText, label: 'Manual Receipt Review Window' },
        { value: 'Built on Your Year Syllabus', label: 'Structured Around Faculty Modules' }
      ],
      langToggleAria: 'التبديل إلى العربية',
      langToggleLabel: 'عربي'
    },
    nav: {
      home: 'Home',
      courses: 'Courses',
      admin: 'Admin Panel',
      reviewQueue: 'Review Queue',
      ctas: 'CTA Management',
      teacher: 'Teacher Panel',
      dashboard: 'Dashboard',
      payments: 'My Payments',
      login: 'Sign In',
      register: 'Register',
      logout: 'Logout',
      skipLink: 'Skip to main content',
      mainNavAria: 'Main navigation',
      brandAria: 'Codeera - كوديرا Home',
      themeLight: 'Light Mode',
      themeDark: 'Dark Mode',
      themeToggleAriaLight: 'Switch to light mode',
      themeToggleAriaDark: 'Switch to dark mode',
      langToggleAria: 'التبديل إلى العربية',
      langToggleLabel: 'عربي',
      menuToggleAria: 'Open main navigation menu',
      menuCloseAria: 'Close main navigation menu',
      roles: {
        superadmin: 'Superadmin',
        admin: 'Admin',
        teacher: 'Instructor',
        student: 'Student'
      },
      footer: {
        tagline: 'Codeera - كوديرا Educational Platform',
        disclaimer: BRAND_DISCLAIMER_AR,
        socialAria: 'Social media links',
        terms: 'Terms & Conditions',
        privacy: 'Privacy Policy',
        refund: 'Refund Policy',
        rights: 'All rights reserved',
        legalNavAria: 'Legal Links'
      }
    },
    home: {
      metaTitle: 'كورسات حاسبات وذكاء اصطناعي الأزهر | فرقة أولى | Codeera - كوديرا',
      metaDesc:
        'Codeera - كوديرا لطلاب حاسبات الأزهر فرقة أولى. شروحات منظمة وكويزات ودفع بفودافون كاش أو إنستاباي.',
      steps: {
        eyebrow: 'How It Works',
        title: 'How to Start Studying on Codeera',
        subtitle: 'Three clear steps from choosing your subject to unlocking access after receipt review.',
        step1Title: 'Choose Your Course by Year & Department',
        step1Desc: 'Browse the courses for your year and department and check the syllabus breakdown.',
        step2Title: 'Pay via Vodafone Cash or InstaPay & Upload Receipt',
        step2Desc: 'Transfer the course fee to our official wallet/account and upload a screenshot of your receipt.',
        step3Title: 'Manual Receipt Review & Access Activation',
        step3Desc: `Our admin team manually reviews your receipt (${SAFE_CONFIG.reviewTimeText}). Once approved, your course content unlocks and you receive confirmation.`
      },
      courses: {
        eyebrow: 'Course Catalog',
        title: 'Available Courses for Your Year',
        viewAll: 'Browse All Courses',
        priceCaption: 'Term Fee',
        onSale: 'Offer',
        details: 'Course Details',
        currency: 'EGP',
        emptyNotice: 'Courses for this year will appear here once published.'
      },
      features: {
        eyebrow: 'What You Get',
        title: 'Organized Material, Quizzes & Support',
        subtitle: 'Built around the syllabus of the Faculty of Computers & AI at Al-Azhar University.',
        items: [
          {
            title: 'Organized by Year & Department',
            desc: 'Built on your academic year syllabus and organized into logical chapters and lessons.'
          },
          {
            title: 'Quizzes With Answer Reviews',
            desc: 'Practice with quizzes that show your score, the correct answers, and solution notes.'
          },
          {
            title: 'Ask via WhatsApp or Course Group',
            desc: 'Reach the team on WhatsApp or inside the course group whenever you have questions.'
          },
          {
            title: 'Pay to an Official Number With Refund Guarantee',
            desc: 'Pay via Vodafone Cash or InstaPay with a full refund if your receipt is rejected.'
          }
        ]
      },
      faq: {
        eyebrow: 'FAQ',
        title: 'Clear Answers Before You Enroll',
        subtitle: 'Everything about payment methods, manual review time, content access, and refunds.',
        items: [
          {
            q: 'What payment methods are available?',
            a: 'You can pay via Vodafone Cash, InstaPay, or any Egyptian mobile wallet, then upload a screenshot of your transfer receipt.'
          },
          {
            q: 'How long does payment review and approval take?',
            a: `Payment receipts are reviewed manually by an admin (${SAFE_CONFIG.reviewTimeText}). Access opens right after approval.`
          },
          {
            q: 'How long is my subscription valid?',
            a: `Your subscription remains valid ${SAFE_CONFIG.subscriptionValidityTextEn}.`
          },
          {
            q: 'What is the refund policy?',
            a: `If your payment receipt is rejected or there was a transfer error, the full amount is returned. Within ${SAFE_CONFIG.refundWindowDays} days from the course activation date, you can also request a full refund with no condition on how much of the course was accessed by contacting us via WhatsApp, processed within 2 to 3 business days. The team reserves the right to review clear misuse cases (such as downloading or sharing content outside the platform).`
          },
          {
            q: 'What is required to watch the video lessons?',
            a: 'Videos open via a dedicated Telegram link, so a linked Telegram account is required.'
          },
          {
            q: 'How can I contact support?',
            a: 'You can reach us directly via our official WhatsApp link in the footer and trust section.'
          },
          {
            q: 'Who is behind Codeera?',
            a: 'Codeera is built by a fellow software developer to organize Al-Azhar FCAI study materials. Codeera is an independent platform and not officially affiliated with Al-Azhar University.'
          }
        ]
      },
      cta: {
        badge: 'Codeera - كوديرا',
        title: 'Ready to Study With a Clear Plan?',
        desc: 'Pick your course, pay via Vodafone Cash or InstaPay, and our team will review your receipt and unlock your access.',
        primary: 'Browse Your Year Courses',
        secondary: 'Ask on WhatsApp'
      }
    },
    courses: {
      metaTitle: 'مواد كلية حاسبات وذكاء اصطناعي الأزهر | فرقة أولى | Codeera - كوديرا',
      metaDesc:
        'تصفح كورسات ومواد كلية الحاسبات والذكاء الاصطناعي جامعة الأزهر فرقة أولى على منصة Codeera - كوديرا.',
      title: 'Academic Course Catalog',
      subtitle: 'Explore coursework for the Faculty of Computers & AI, Al-Azhar University.',
      desc: 'Built on your year syllabus with structured lessons, quizzes with answer reviews, and direct support via WhatsApp or the course group.',
      adminTitle: 'Course Management Privileges',
      adminDesc: 'You can create new courses or update content and pricing directly from here.',
      addCourse: 'Add New Course',
      searchPlaceholder: 'Search by title or code (e.g. C++, Discrete Math, Physics)...',
      searchAria: 'Search courses',
      filterSectionAria: 'Search and filter tools',
      filterFormAria: 'Filter courses by academic year and department',
      yearLabel: 'Academic Year:',
      allYears: 'All Academic Years',
      generalForAll: 'General for all (All Academic Years)',
      deptLabel: 'Department:',
      allDepts: 'All Departments',
      applyFilter: 'Apply Filters',
      resetFilter: 'Reset Filters',
      priceCaption: 'Course Fee',
      currency: 'EGP',
      exploreDetails: 'Details & Enroll',
      onSale: 'Offer',
      emptyTitle: 'No courses match your current search filters.',
      emptyDesc: 'Try adjusting your search query or selecting a different year and department.',
      clearFilters: 'Clear filters and search'
    },
    auth: {
      login: {
        metaTitle: 'Sign In | Codeera - كوديرا',
        metaDesc: 'Sign in to your account on Codeera - كوديرا educational platform.',
        badge: 'Student & Staff Portal',
        title: 'Sign In',
        subtitle: 'Enter your email and password to access your account and coursework.',
        emailLabel: 'Email Address',
        emailPlaceholder: 'name@example.com',
        passwordLabel: 'Password',
        passwordPlaceholder: '••••••••',
        forgotPasswordLink: 'Forgot password?',
        submit: 'Sign In',
        submitting: 'Signing in...',
        noAccount: "Don't have an account yet?",
        registerLink: 'Create a new student account',
        alreadyLoggedInTitle: 'You are already signed in',
        alreadyLoggedInWelcome: 'Welcome back,',
        goToDashboard: 'Go to Dashboard',
        browseCourses: 'Browse Courses',
        errors: {
          required: 'Please enter your email and password.',
          invalid: 'Invalid credentials, please try again.',
          generic: 'An error occurred during sign in.'
        }
      },
      forgotPassword: {
        metaTitle: 'Forgot Password | Codeera - كوديرا',
        metaDesc: 'Recover and reset your account password on Codeera - كوديرا.',
        badge: 'Account Recovery',
        title: 'Forgot Password?',
        subtitle: 'Enter your registered email address and we will send you a reset link.',
        emailLabel: 'Email Address',
        emailPlaceholder: 'name@example.com',
        submit: 'Send Password Reset Link',
        submitting: 'Sending...',
        backToLogin: 'Back to Sign In',
        successTitle: 'Reset Link Sent',
        successMessage:
          'If an account exists with this email, you will receive a password reset link within a few minutes.',
        resendNote: 'Did not receive the email? Check your Spam folder or try again shortly.',
        errors: {
          required: 'Please enter your email address.',
          invalidEmail: 'Please enter a valid email address.',
          throttled: 'Too many attempts. Please wait before trying again.',
          generic: 'An error occurred while sending the reset link. Please try again.'
        }
      },
      resetPassword: {
        metaTitle: 'Reset Password | Codeera - كوديرا',
        metaDesc: 'Set a new password for your account on Codeera - كوديرا.',
        badge: 'Account Security',
        title: 'Set New Password',
        subtitle: 'Please enter and confirm your new password to access your account.',
        emailLabel: 'Email Address',
        passwordLabel: 'New Password',
        passwordPlaceholder: 'At least 8 characters',
        confirmPasswordLabel: 'Confirm New Password',
        confirmPasswordPlaceholder: 'Re-enter your new password',
        submit: 'Update Password',
        submitting: 'Saving password...',
        backToLogin: 'Back to Sign In',
        requestNewLink: 'Request a New Link',
        successTitle: 'Password Updated',
        successMessage: 'Your account password has been updated. You can now sign in.',
        redirecting: 'Redirecting to sign in page...',
        invalidLinkTitle: 'Invalid or Expired Reset Link',
        invalidLinkMessage:
          'This password reset link appears to be invalid or has expired (valid for 60 minutes). You can request a new link at any time.',
        errors: {
          required: 'Please fill in all required fields.',
          passwordMin: 'Password must be at least 8 characters.',
          passwordMismatch: 'Passwords do not match.',
          tokenInvalid: 'This password reset link is invalid or has expired. Please request a new one.',
          throttled: 'Too many attempts. Please wait before trying again.',
          generic: 'An error occurred while resetting your password.'
        }
      },
      register: {
        metaTitle: 'Create Student Account | Codeera - كوديرا',
        metaDesc: 'Register a new student account on Codeera - كوديرا.',
        badge: 'New Student Account',
        title: 'Create Student Account',
        subtitle: 'Register your academic details to access your courses, lectures, and quizzes.',
        nameLabel: 'Full Name',
        namePlaceholder: 'Mohamed Ahmed Ali',
        emailLabel: 'Email Address',
        emailPlaceholder: 'name@example.com',
        passwordLabel: 'Password',
        passwordPlaceholder: 'At least 8 characters',
        confirmPasswordLabel: 'Confirm Password',
        confirmPasswordPlaceholder: 'Re-enter password',
        branchLabel: 'Campus Branch',
        branchBoys: 'Boys (Cairo)',
        branchGirls: 'Girls (Cairo)',
        academicYearLabel: 'Academic Year',
        departmentLabel: 'Department',
        telegramLabel: 'Telegram Username (Optional)',
        telegramPlaceholder: '@username',
        phoneLabel: 'Mobile Wallet Number (Vodafone Cash / InstaPay)',
        phonePlaceholder: '01012345678',
        submit: 'Create Account & Continue',
        submitting: 'Creating account...',
        hasAccount: 'Already have an account?',
        loginLink: 'Sign In',
        years: {
          first: '1st Year',
          second: '2nd Year'
        },
        departments: {
          cs: 'Computer Science (CS)',
          cy: 'Cybersecurity (CY)',
          ds: 'Data Science (DS)',
          ai: 'Artificial Intelligence (AI)'
        },
        errors: {
          passwordMismatch: 'Password and confirmation do not match.',
          passwordMinLength: 'Password must be at least 8 characters long.',
          failed: 'Could not create account, please verify your inputs.',
          generic: 'An error occurred during registration.'
        }
      },
      guard: {
        loginRequiredTitle: 'Sign In Required',
        loginRequiredDescPrefix: 'This page is restricted to',
        loginRequiredDescSuffix: 'only. Please sign in with an authorized account.',
        accessDeniedTitle: 'Access Denied',
        accessDeniedDescCurrent: 'You are currently signed in as',
        accessDeniedDescRequired: 'while this dashboard is exclusively for',
        cannotAccessTitle: 'Unable to Load Dashboard',
        defaultError: 'An error occurred while verifying permissions or loading data.',
        loginBtn: 'Sign In',
        homeBtn: 'Return Home',
        browseCoursesBtn: 'Browse Course Catalog',
        switchAccountBtn: 'Switch Account (Logout)',
        retryBtn: 'Try Again',
        roles: {
          admin: 'Administrators',
          teacher: 'Instructors & Faculty',
          student: 'Registered Students',
          superadmin: 'Superadmin'
        }
      }
    }
  }
};

const STORAGE_KEY = 'codeera_locale';

export const currentLocale = writable<Locale>('ar');

export const t = derived(currentLocale, ($locale) => translations[$locale]);

export const currentHeroTranslations = derived(
  currentLocale,
  ($locale) => translations[$locale].hero
);

export function applyLocaleToDocument(locale: Locale) {
  if (typeof document !== 'undefined') {
    document.documentElement.lang = locale;
    document.documentElement.dir = locale === 'en' ? 'ltr' : 'rtl';
  }
}

export function initLocale() {
  applyLocaleToDocument('ar');
  currentLocale.set('ar');
}

export function setLocale(locale: Locale) {
  currentLocale.set(locale);
  applyLocaleToDocument(locale);
  if (typeof window !== 'undefined') {
    try {
      localStorage.setItem(STORAGE_KEY, locale);
    } catch {
      // ignore storage errors
    }
  }
}

export function toggleLocale() {
  let active: Locale = 'ar';
  const unsub = currentLocale.subscribe((val) => {
    active = val;
  });
  unsub();

  const next: Locale = active === 'ar' ? 'en' : 'ar';
  setLocale(next);
}

/**
 * Helper to format prices from cents according to active locale
 */
export function formatPrice(cents: number, locale: Locale = 'ar'): string {
  const pounds = Math.round(cents / 100);
  return locale === 'en' ? `${pounds} EGP` : `${pounds} ج.م`;
}

/**
 * Helper to select localized text from backend {ar, en} object
 */
export function getLocalizedText(
  field: { ar?: string; en?: string } | string | null | undefined,
  locale: Locale = 'ar'
): string {
  if (!field) return '';
  if (typeof field === 'string') return field;
  if (locale === 'en') {
    return field.en || field.ar || '';
  }
  return field.ar || field.en || '';
}
