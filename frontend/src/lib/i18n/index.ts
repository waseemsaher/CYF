import { writable, derived } from 'svelte/store';

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
      badge: 'منصة تعليمية لطلاب حاسبات الأزهر',
      titlePrefix: 'شروحات برمجية مركزة،',
      titleHighlight: 'خطوتك الواثقة نحو الامتياز الأكاديمي.',
      subheading: 'منصة Codeera توفر محتوى دراسي متخصص يغطي مناهج كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر، مع دعم أكاديمي مباشر ومتابعة مستمرة لحد ما توصل للنتيجة اللي تستحقها.',
      description: 'منصة Codeera توفر محتوى دراسي متخصص يغطي مناهج كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر، مع دعم أكاديمي مباشر ومتابعة مستمرة لحد ما توصل للنتيجة اللي تستحقها.',
      typewriterPrefix: 'المسارات الأكاديمية:',
      typewriterPhrases: [
        'تراك الذكاء الاصطناعي وتعلم الآلة',
        'علوم الحاسب والخوارزميات المتقدمة',
        'نظم المعلومات وقواعد البيانات الحديثة',
        'بيئة تفاعلية لاجتياز الامتحانات بتفوق'
      ],
      primaryCta: 'تصفح الكورسات',
      secondaryCta: 'إنشاء حساب',
      dashboardCta: 'لوحة التحكم',
      stats: [
        { value: '+10', label: 'مقررات تخصصية متكاملة' },
        { value: '100%', label: 'تطابق مع توصيف المقرر الأكاديمي' },
        { value: 'فوري', label: 'تفعيل المحتوى بعد اعتماد الدفع' }
      ],
      langToggleAria: 'Switch to English',
      langToggleLabel: 'English'
    },
    nav: {
      home: 'الرئيسية',
      courses: 'الدورات',
      admin: 'لوحة الإدارة',
      reviewQueue: 'طابور المراجعة',
      teacher: 'لوحة المعلم',
      dashboard: 'لوحة الطالب',
      payments: 'مدفوعاتي',
      login: 'دخول',
      register: 'حساب جديد',
      logout: 'خروج',
      skipLink: 'انتقل إلى المحتوى الرئيسي',
      mainNavAria: 'التنقل الرئيسي',
      brandAria: 'الصفحة الرئيسية لمنصة Codeera',
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
        tagline: 'منصة Codeera التعليمية',
        socialAria: 'حسابات التواصل الاجتماعي',
        terms: 'الشروط والأحكام',
        privacy: 'الخصوصية',
        refund: 'الاسترداد',
        rights: 'جميع الحقوق محفوظة',
        legalNavAria: 'روابط المنصة القانونية'
      }
    },
    home: {
      metaTitle: 'منصة Codeera | شروحات ومقررات برمجية تفاعلية',
      metaDesc: 'منصة Codeera التعليمية التفاعلية لشروحات ومقررات واختبارات البرمجة والذكاء الاصطناعي.',
      steps: {
        eyebrow: 'خطوات سريعة',
        title: 'كيف تبدأ دراستك معنا؟',
        subtitle: 'ثلاث خطوات بسيطة ومباشرة تفصلك عن المحتوى الأكاديمي ومجموعات المناقشة.',
        step1Title: 'اختر مقررك الدراسي',
        step1Desc: 'تصفح المقررات المتاحة لفرقتك وقسمك (علوم حاسب، نظم، ذكاء اصطناعي)، واطلع على تفاصيل المنهج وشروحاته.',
        step2Title: 'سدد الرسوم وارفع الإيصال',
        step2Desc: 'حول الرسوم بسهولة عبر فودافون كاش أو إنستاباي، ثم ارفع لقطة شاشة للإيصال في نموذج الاشتراك المباشر.',
        step3Title: 'ابدأ دراستك فوراً',
        step3Desc: 'فور اعتماد الدفع، يفتح لك المحتوى التعليمي بالكامل ويتم تفعيل اشتراكك تلقائياً — بدون انتظار أو تعقيد.'
      },
      courses: {
        eyebrow: 'المقررات الدراسية',
        title: 'أحدث المقررات المتاحة',
        viewAll: 'عرض كامل الدليل الأكاديمي',
        priceCaption: 'رسوم المقرر',
        onSale: 'خصم ساري',
        details: 'التفاصيل',
        currency: 'ج.م',
        emptyNotice: 'ستظهر المقررات الدراسية المتاحة هنا فور إطلاقها.'
      },
      features: {
        eyebrow: 'لماذا Codeera؟',
        title: 'بيئة أكاديمية متكاملة لطلاب الحاسبات',
        subtitle: 'صممت منصة Codeera خصيصاً لتلائم طبيعة ومناهج كلية الحاسبات بجامعة الأزهر.',
        items: [
          {
            title: 'تغطية شاملة لمقررات الكلية',
            desc: 'محتوى مصور ومكتوب متوافق 100% مع توصيف المقررات الأكاديمية والمناهج المعتمدة.'
          },
          {
            title: 'تفعيل فوري للمحتوى',
            desc: 'محتوى المقرر بيفتح أمامك مباشرة فور اعتماد الدفع، بدون أي انتظار أو خطوات إضافية.'
          },
          {
            title: 'اختبارات تقييم ذاتي ذكية',
            desc: 'اختبر معلوماتك بعد كل باب دراسي عبر نظام كويزات تفاعلي يعرض نتيجتك وحلول الأسئلة.'
          },
          {
            title: 'دفع آمن ومعالجة مباشرة',
            desc: 'ادفع عبر المحافظ الإلكترونية المألوفة (فودافون كاش، إنستاباي) مع نظام توثيق فوري للإيصالات.'
          }
        ]
      },
      faq: {
        eyebrow: 'الأسئلة الشائعة',
        title: 'كل ما تريد معرفته عن المنصة',
        subtitle: 'إجابات واضحة ومباشرة عن كافة تساؤلات الطلاب.',
        items: [
          {
            q: 'إزاي أقدر أتواصل مع فريق المادة أو أحصل على دعم خلال المقرر؟',
            a: 'بعد اعتماد إيصال السداد، هتلاقي في لوحة التحكم رابط الانضمام لقناة النقاش الخاصة بالمقرر. فور إرسال طلب الانضمام، هيتم تفعيله تلقائياً وفوراً من غير انتظار.'
          },
          {
            q: 'ما هي طرق الدفع المتاحة للاشتراك؟',
            a: 'نوفر الدفع السهل والمباشر عبر فودافون كاش (Vodafone Cash)، أو إنستاباي (InstaPay)، أو أي محفظة إلكترونية بنكية. بعد إتمام التحويل، تقوم برفع صورة إيصال التحويل في صفحة إتمام الطلب لتأكيد اشتراكك.'
          },
          {
            q: 'كم يستغرق وقت مراجعة وتأكيد الاشتراك؟',
            a: 'تتم مراجعة إيصالات الدفع بواسطة فريق الإدارة بانتظام وفي أسرع وقت ممكن (عادة خلال دقائق إلى ساعات معدودة). ستصلك رسالة تأكيد عبر البريد الإلكتروني فور الاعتماد مع فتح صلاحيات المادة فوراً.'
          },
          {
            q: 'ما مدة صلاحية اشتراكي في المادة؟',
            a: 'يستمر اشتراكك فعالاً ومتاحاً لك حتى نهاية الفصل الدراسي الرسمي للمقرر، مع فترة سماح إضافية لمراجعة المحتوى والاختبارات حتى انتهاء موسم الامتحانات النهائية.'
          }
        ]
      },
      cta: {
        badge: 'ابدأ دراستك الآن',
        title: 'جاهز للتفوق في فصلك الدراسي؟',
        desc: 'انضم لزملائك في كلية الحاسبات والذكاء الاصطناعي واستفد من شروحات المقررات والدعم الأكاديمي المباشر اليوم.',
        primary: 'استكشف جميع المقررات',
        secondary: 'إنشاء حساب طالب'
      }
    },
    courses: {
      metaTitle: 'دليل المقررات الأكاديمية | منصة Codeera',
      metaDesc: 'تصفح المقررات الدراسية المتاحة لطلاب كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر.',
      title: 'دليل المقررات الأكاديمية',
      subtitle: 'تصفح جميع المواد الدراسية التخصصية لكلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر.',
      desc: 'محتوى تعليمي أكاديمي دقيق، شروحات مسجلة، بنك أسئلة واختبارات تفاعلية، مع وصول فوري لمجموعات التليجرام الخاصة بكل مادة.',
      adminTitle: 'صلاحيات إدارة المقررات',
      adminDesc: 'يمكنك إنشاء مقررات جديدة أو تعديل المحتوى والأسعار مباشرة من هنا.',
      addCourse: 'إضافة مقرر دراسي جديد',
      searchPlaceholder: 'ابحث بالاسم أو الرمز (مثال: برمجيات، ذكاء اصطناعي، CS)...',
      searchAria: 'البحث في المقررات',
      filterSectionAria: 'أدوات البحث والتصفية',
      filterFormAria: 'تصفية الدورات حسب الفرقة والقسم',
      yearLabel: 'الفرقة الدراسية:',
      allYears: 'كل الفرق الدراسية',
      deptLabel: 'القسم الأكاديمي:',
      allDepts: 'جميع الأقسام',
      applyFilter: 'تطبيق الفلتر',
      resetFilter: 'إلغاء التصفية',
      priceCaption: 'رسوم المقرر',
      currency: 'جنيه',
      exploreDetails: 'التفاصيل والتسجيل',
      onSale: 'خصم ساري',
      emptyTitle: 'لا توجد مقررات دراسية تطابق خيارات البحث الحالية.',
      emptyDesc: 'جرب تغيير معايير البحث أو اختيار قسم وفرقة دراسية أخرى لعرض المواد المتاحة.',
      clearFilters: 'مسح خيارات التصفية والبحث'
    },
    auth: {
      login: {
        metaTitle: 'تسجيل الدخول | منصة Codeera',
        metaDesc: 'تسجيل الدخول إلى حسابك في منصة Codeera التعليمية.',
        badge: 'بوابة الطلاب والمعلمين والإدارة',
        title: 'تسجيل الدخول',
        subtitle: 'أدخل بريدك الإلكتروني وكلمة المرور لمتابعة حسابك ومقرراتك.',
        emailLabel: 'البريد الإلكتروني',
        emailPlaceholder: 'name@example.com',
        passwordLabel: 'كلمة المرور',
        passwordPlaceholder: '••••••••',
        forgotPasswordLink: 'نسيت كلمة السر؟',
        submit: 'دخول',
        submitting: 'جاري تسجيل الدخول...',
        noAccount: 'ليس لديك حساب بعد؟',
        registerLink: 'إنشاء حساب طالب جديد',
        alreadyLoggedInTitle: 'أنت مسجل الدخول بالفعل',
        alreadyLoggedInWelcome: 'مرحباً بك مجدداً،',
        goToDashboard: 'الدخول إلى لوحة التحكم',
        browseCourses: 'تصفح المقررات',
        errors: {
          required: 'يرجى إدخال البريد الإلكتروني وكلمة المرور.',
          invalid: 'بيانات الدخول غير صحيحة، يرجى المحاولة مرة أخرى.',
          generic: 'حدث خطأ أثناء تسجيل الدخول.'
        }
      },
      forgotPassword: {
        metaTitle: 'استعادة كلمة المرور | منصة Codeera',
        metaDesc: 'استعادة وتعيين كلمة المرور لحسابك في منصة Codeera التعليمية.',
        badge: 'استعادة الحساب',
        title: 'نسيت كلمة المرور؟',
        subtitle: 'أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور.',
        emailLabel: 'البريد الإلكتروني',
        emailPlaceholder: 'name@example.com',
        submit: 'إرسال رابط استعادة كلمة المرور',
        submitting: 'جاري الإرسال...',
        backToLogin: 'العودة لتسجيل الدخول',
        successTitle: 'تم إرسال الرابط',
        successMessage: 'إذا كان هذا البريد مسجلاً لدينا، فستصلك رسالة تحتوي على رابط لإعادة تعيين كلمة السر خلال دقائق.',
        resendNote: 'لم تصلك الرسالة؟ تأكد من مجلد الرسائل غير المرغوب فيها (Spam) أو حاول مجدداً بعد قليل.',
        errors: {
          required: 'يرجى إدخال البريد الإلكتروني.',
          invalidEmail: 'يرجى إدخال بريد إلكتروني صالح.',
          throttled: 'لقد تجاوزت عدد المحاولات المسموح بها. يرجى الانتظار والمحاولة لاحقاً.',
          generic: 'حدث خطأ أثناء إرسال الرابط، يرجى المحاولة مرة أخرى.'
        }
      },
      resetPassword: {
        metaTitle: 'إعادة تعيين كلمة المرور | منصة Codeera',
        metaDesc: 'تعيين كلمة مرور جديدة لحسابك في منصة Codeera التعليمية.',
        badge: 'أمان الحساب',
        title: 'تعيين كلمة المرور الجديدة',
        subtitle: 'يرجى إدخال كلمة المرور الجديدة وتأكيدها لمتابعة الدخول لحسابك.',
        emailLabel: 'البريد الإلكتروني',
        passwordLabel: 'كلمة المرور الجديدة',
        passwordPlaceholder: '8 أحرف على الأقل',
        confirmPasswordLabel: 'تأكيد كلمة المرور الجديدة',
        confirmPasswordPlaceholder: 'أعد إدخال كلمة المرور',
        submit: 'تغيير كلمة المرور',
        submitting: 'جاري حفظ كلمة المرور...',
        backToLogin: 'العودة لتسجيل الدخول',
        requestNewLink: 'طلب رابط جديد',
        successTitle: 'تم تغيير كلمة المرور بنجاح!',
        successMessage: 'تم تحديث كلمة المرور لحسابك. يمكنك الآن تسجيل الدخول بكلمة المرور الجديدة.',
        redirecting: 'جاري نقلك لصفحة الدخول...',
        invalidLinkTitle: 'رابط غير صالح أو منتهي الصلاحية',
        invalidLinkMessage: 'يبدو أن رابط إعادة تعيين كلمة المرور غير صالح أو انتهت مدة صلاحيته (صالح لمدة 60 دقيقة). يمكنك طلب رابط جديد في أي وقت.',
        errors: {
          required: 'يرجى ملء جميع الحقول المطلوبة.',
          passwordMin: 'كلمة المرور يجب أن تتكون من 8 أحرف على الأقل.',
          passwordMismatch: 'كلمتا المرور غير متطابقتين.',
          tokenInvalid: 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية. يرجى طلب رابط جديد.',
          throttled: 'لقد تجاوزت عدد المحاولات المسموح بها. يرجى الانتظار والمحاولة لاحقاً.',
          generic: 'حدث خطأ أثناء إعادة تعيين كلمة المرور.'
        }
      },
      register: {
        metaTitle: 'إنشاء حساب طالب جديد | منصة Codeera',
        metaDesc: 'تسجيل حساب طالب جديد في منصة Codeera التعليمية للبرمجة وعلوم الحاسب.',
        badge: 'انضم لزملائك',
        title: 'إنشاء حساب طالب',
        subtitle: 'سجل بياناتك الأكاديمية للوصول إلى مقرراتك وشروحات المناهج واختباراتها.',
        nameLabel: 'الاسم بالكامل (ثلاثي أو رباعي)',
        namePlaceholder: 'محمد أحمد علي',
        emailLabel: 'البريد الإلكتروني',
        emailPlaceholder: 'name@example.com',
        passwordLabel: 'كلمة المرور',
        passwordPlaceholder: '8 أحرف على الأقل',
        confirmPasswordLabel: 'تأكيد كلمة المرور',
        confirmPasswordPlaceholder: 'أعد إدخال كلمة المرور',
        branchLabel: 'فرع الكلية',
        branchBoys: 'بنين (القاهرة)',
        branchGirls: 'بنات (القاهرة)',
        academicYearLabel: 'السنة الدراسية',
        departmentLabel: 'القسم الأكاديمي',
        telegramLabel: 'اسم مستخدم تليجرام (اختياري)',
        telegramPlaceholder: '@username',
        phoneLabel: 'رقم المحفظة الإلكترونية (فودافون كاش / إنستاباي - لاسترداد المبالغ)',
        phonePlaceholder: '01XXXXXXXXX',
        submit: 'إنشاء الحساب وبدء التعلم',
        submitting: 'جاري إنشاء الحساب...',
        hasAccount: 'لديك حساب بالفعل؟',
        loginLink: 'تسجيل الدخول',
        years: {
          first: 'السنة الأولى',
          second: 'السنة الثانية'
        },
        departments: {
          cs: 'علوم الحاسب (CS)',
          cy: 'الأمن السيبراني (CY)',
          ds: 'علم البيانات (DS)',
          ai: 'الذكاء الاصطناعي (AI)'
        },
        errors: {
          passwordMismatch: 'كلمة المرور وتأكيدها غير متطابقين.',
          passwordMinLength: 'يجب ألا تقل كلمة المرور عن 8 أحرف.',
          failed: 'تعذر إنشاء الحساب، يرجى مراجعة البيانات المدخلة.',
          generic: 'حدث خطأ أثناء إنشاء الحساب.'
        }
      },
      guard: {
        loginRequiredTitle: 'تسجيل الدخول مطلوب',
        loginRequiredDescPrefix: 'هذه الصفحة مخصصة لـ',
        loginRequiredDescSuffix: 'فقط. يرجى تسجيل الدخول بحساب معتمد للوصول إلى لوحة التحكم.',
        accessDeniedTitle: 'غير مصرح لك بالوصول',
        accessDeniedDescCurrent: 'أنت مسجل حالياً بحساب',
        accessDeniedDescRequired: 'وهذه اللوحة مخصصة حصرياً لـ',
        cannotAccessTitle: 'تعذر الوصول إلى اللوحة',
        defaultError: 'حدث خطأ أثناء التحقق من الصلاحيات أو تحميل البيانات.',
        loginBtn: 'تسجيل الدخول',
        homeBtn: 'العودة للرئيسية',
        browseCoursesBtn: 'تصفح المقررات الدراسية',
        switchAccountBtn: 'تبديل الحساب (خروج)',
        retryBtn: 'إعادة المحاولة',
        roles: {
          admin: 'المسؤولين',
          teacher: 'أعضاء هيئة التدريس والمحاضرين',
          student: 'الطلاب المسجلين',
          superadmin: 'المدير العام'
        }
      }
    }
  },
  en: {
    hero: {
      badge: 'Educational Platform for FCAI Al-Azhar Students',
      titlePrefix: 'Focused Tech Curriculum,',
      titleHighlight: 'Your Definitive Path to Academic Excellence.',
      subheading: 'Codeera provides specialized coursework tailored for Faculty of Computers & Artificial Intelligence Al-Azhar students, with direct academic support and continuous follow-up to achieve the results you deserve.',
      description: 'Codeera provides specialized coursework tailored for Faculty of Computers & Artificial Intelligence Al-Azhar students, with direct academic support and continuous follow-up to achieve the results you deserve.',
      typewriterPrefix: 'Academic Tracks:',
      typewriterPhrases: [
        'Artificial Intelligence & Machine Learning',
        'Advanced Computer Science & Algorithms',
        'Information Systems & Modern Databases',
        'Interactive Platform for Exam Readiness'
      ],
      primaryCta: 'Browse Courses',
      secondaryCta: 'Create Account',
      dashboardCta: 'My Dashboard',
      stats: [
        { value: '+10', label: 'Comprehensive Tech Courses' },
        { value: '100%', label: 'Match with Academic Course Specifications' },
        { value: 'Instant', label: 'Content Access Upon Payment Approval' }
      ],
      langToggleAria: 'التبديل إلى العربية',
      langToggleLabel: 'عربي'
    },
    nav: {
      home: 'Home',
      courses: 'Courses',
      admin: 'Admin Panel',
      reviewQueue: 'Review Queue',
      teacher: 'Teacher Panel',
      dashboard: 'Dashboard',
      payments: 'My Payments',
      login: 'Sign In',
      register: 'Register',
      logout: 'Logout',
      skipLink: 'Skip to main content',
      mainNavAria: 'Main navigation',
      brandAria: 'Codeera Platform Home',
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
        tagline: 'Codeera Educational Platform',
        socialAria: 'Social media links',
        terms: 'Terms & Conditions',
        privacy: 'Privacy Policy',
        refund: 'Refund Policy',
        rights: 'All rights reserved',
        legalNavAria: 'Legal Links'
      }
    },
    home: {
      metaTitle: 'Codeera Platform | Interactive Programming & CS Courses',
      metaDesc: 'Codeera is an interactive educational platform for programming, computer science, and AI curriculum with direct academic support.',
      steps: {
        eyebrow: 'Quick Steps',
        title: 'How to Start Learning With Us',
        subtitle: 'Three direct steps connecting you to accredited academic content and private discussion groups.',
        step1Title: 'Choose Your Course',
        step1Desc: 'Browse courses tailored to your academic year and department (CS, IS, AI) and preview syllabus outlines.',
        step2Title: 'Pay Fees & Upload Receipt',
        step2Desc: 'Transfer securely via Vodafone Cash, InstaPay, or mobile wallets, then upload the receipt screenshot.',
        step3Title: 'Start Studying Immediately',
        step3Desc: 'Once payment is approved, your full course content is unlocked and your access is activated automatically — no waiting or hassle.'
      },
      courses: {
        eyebrow: 'Course Catalog',
        title: 'Latest Available Courses',
        viewAll: 'View Full Academic Catalog',
        priceCaption: 'Course Fee',
        onSale: 'On Sale',
        details: 'Details',
        currency: 'EGP',
        emptyNotice: 'Available courses will appear here once released.'
      },
      features: {
        eyebrow: 'Why Codeera?',
        title: 'Integrated Academic Platform for Tech Students',
        subtitle: 'Codeera is designed specifically around the syllabus and exam standards of the Faculty of Computers & AI at Al-Azhar University.',
        items: [
          {
            title: 'Full Curriculum Coverage',
            desc: 'Video lectures and notes 100% aligned with verified academic course specifications.'
          },
          {
            title: 'Instant Content Activation',
            desc: 'Course content opens directly upon payment approval, without any waiting or extra steps.'
          },
          {
            title: 'Smart Self-Assessment Quizzes',
            desc: 'Test your understanding after each module with interactive quizzes, instant scores, and answer keys.'
          },
          {
            title: 'Direct & Secure Payment',
            desc: 'Pay with popular local payment methods (Vodafone Cash, InstaPay) with streamlined receipt verification.'
          }
        ]
      },
      faq: {
        eyebrow: 'Frequently Asked Questions',
        title: 'Everything You Need to Know',
        subtitle: 'Direct and clear answers to common student inquiries.',
        items: [
          {
            q: 'How can I contact course instructors or get support during the course?',
            a: 'After your payment receipt is approved, you will find the discussion channel link in your dashboard. Upon sending a join request, it is activated automatically and immediately without waiting.'
          },
          {
            q: 'What payment methods are supported?',
            a: 'We support straightforward payments via Vodafone Cash, InstaPay, and standard Egyptian bank digital wallets. Once you transfer, submit a screenshot of your transfer receipt to confirm your subscription.'
          },
          {
            q: 'How long does subscription verification take?',
            a: 'Payment receipts are reviewed regularly by administrators as quickly as possible (usually within minutes to a few hours). You will receive an email confirmation and immediate access upon approval.'
          },
          {
            q: 'How long does my course subscription remain valid?',
            a: 'Your access remains active through the end of the official academic semester, plus a generous extension period for review and exam preparation until finals finish.'
          }
        ]
      },
      cta: {
        badge: 'Start Learning Now',
        title: 'Ready to Excel This Semester?',
        desc: 'Join your classmates in the Faculty of Computers & Artificial Intelligence and benefit from course lectures and direct academic support today.',
        primary: 'Explore All Courses',
        secondary: 'Create Student Account'
      }
    },
    courses: {
      metaTitle: 'Academic Course Catalog | Codeera',
      metaDesc: 'Browse available courses for Faculty of Computers & Artificial Intelligence Al-Azhar University students.',
      title: 'Academic Course Catalog',
      subtitle: 'Explore specialized coursework for the Faculty of Computers & Artificial Intelligence, Al-Azhar University.',
      desc: 'Precise academic curriculum, recorded lectures, question banks, and interactive quizzes with instant access to course Telegram groups.',
      adminTitle: 'Course Management Privileges',
      adminDesc: 'You can create new courses or update content and pricing directly from here.',
      addCourse: 'Add New Course',
      searchPlaceholder: 'Search by title or code (e.g. Software, AI, CS)...',
      searchAria: 'Search courses',
      filterSectionAria: 'Search and filter tools',
      filterFormAria: 'Filter courses by academic year and department',
      yearLabel: 'Academic Year:',
      allYears: 'All Academic Years',
      deptLabel: 'Department:',
      allDepts: 'All Departments',
      applyFilter: 'Apply Filters',
      resetFilter: 'Reset Filters',
      priceCaption: 'Course Fee',
      currency: 'EGP',
      exploreDetails: 'Details & Enroll',
      onSale: 'On Sale',
      emptyTitle: 'No courses match your current search filters.',
      emptyDesc: 'Try adjusting your search query or selecting a different year and department to view courses.',
      clearFilters: 'Clear filters and search'
    },
    auth: {
      login: {
        metaTitle: 'Sign In | Codeera Platform',
        metaDesc: 'Sign in to your account on Codeera educational platform.',
        badge: 'Portal for Students, Instructors & Staff',
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
        metaTitle: 'Forgot Password | Codeera Platform',
        metaDesc: 'Recover and reset your account password on Codeera educational platform.',
        badge: 'Account Recovery',
        title: 'Forgot Password?',
        subtitle: 'Enter your registered email address and we will send you a reset link.',
        emailLabel: 'Email Address',
        emailPlaceholder: 'name@example.com',
        submit: 'Send Reset Link',
        submitting: 'Sending...',
        backToLogin: 'Back to Sign In',
        successTitle: 'Reset Link Sent',
        successMessage: 'If this email is registered with us, you will receive an email with a password reset link within a few minutes.',
        resendNote: "Didn't receive the email? Check your spam folder or try again in a few minutes.",
        errors: {
          required: 'Please enter your email address.',
          invalidEmail: 'Please enter a valid email address.',
          throttled: 'Too many attempts. Please wait and try again later.',
          generic: 'An unexpected error occurred. Please try again.'
        }
      },
      resetPassword: {
        metaTitle: 'Reset Password | Codeera Platform',
        metaDesc: 'Set a new password for your account on Codeera educational platform.',
        badge: 'Account Security',
        title: 'Reset Your Password',
        subtitle: 'Enter and confirm your new password to restore access to your account.',
        emailLabel: 'Email Address',
        passwordLabel: 'New Password',
        passwordPlaceholder: 'At least 8 characters',
        confirmPasswordLabel: 'Confirm New Password',
        confirmPasswordPlaceholder: 'Re-enter your new password',
        submit: 'Reset Password',
        submitting: 'Resetting password...',
        backToLogin: 'Back to Sign In',
        requestNewLink: 'Request a new reset link',
        successTitle: 'Password Reset Successfully!',
        successMessage: 'Your password has been updated. You can now log in with your new password.',
        redirecting: 'Redirecting to sign in...',
        invalidLinkTitle: 'Invalid or Expired Link',
        invalidLinkMessage: 'This password reset link is invalid or has expired (links expire after 60 minutes). Please request a new link.',
        errors: {
          required: 'Please fill in all required fields.',
          passwordMin: 'Password must be at least 8 characters long.',
          passwordMismatch: 'Passwords do not match.',
          tokenInvalid: 'This password reset link is invalid or has expired. Please request a new one.',
          throttled: 'Too many attempts. Please wait and try again later.',
          generic: 'An error occurred while resetting your password.'
        }
      },
      register: {
        metaTitle: 'Create Student Account | Codeera Platform',
        metaDesc: 'Create a student account on Codeera educational platform for computer science and programming.',
        badge: 'Join Your Peers',
        title: 'Create Student Account',
        subtitle: 'Enter your academic information to access courses, lecture notes, and quizzes.',
        nameLabel: 'Full Name',
        namePlaceholder: 'e.g. John Doe',
        emailLabel: 'Email Address',
        emailPlaceholder: 'name@example.com',
        passwordLabel: 'Password',
        passwordPlaceholder: 'At least 8 characters',
        confirmPasswordLabel: 'Confirm Password',
        confirmPasswordPlaceholder: 'Re-enter your password',
        branchLabel: 'Faculty Branch',
        branchBoys: 'Boys (Cairo)',
        branchGirls: 'Girls (Cairo)',
        academicYearLabel: 'Academic Year',
        departmentLabel: 'Academic Department',
        telegramLabel: 'Telegram Username (optional)',
        telegramPlaceholder: '@username',
        phoneLabel: 'E-Wallet Phone Number (Vodafone Cash / InstaPay - for refunds)',
        phonePlaceholder: '01XXXXXXXXX',
        submit: 'Create Account & Start Learning',
        submitting: 'Creating account...',
        hasAccount: 'Already have an account?',
        loginLink: 'Sign In',
        years: {
          first: 'First Year',
          second: 'Second Year'
        },
        departments: {
          cs: 'Computer Science (CS)',
          cy: 'Cybersecurity (CY)',
          ds: 'Data Science (DS)',
          ai: 'Artificial Intelligence (AI)'
        },
        errors: {
          passwordMismatch: 'Passwords do not match.',
          passwordMinLength: 'Password must be at least 8 characters long.',
          failed: 'Could not create account, please check the entered details.',
          generic: 'An error occurred while creating your account.'
        }
      },
      guard: {
        loginRequiredTitle: 'Sign In Required',
        loginRequiredDescPrefix: 'This page is restricted to',
        loginRequiredDescSuffix: 'only. Please sign in with an authorized account to access this panel.',
        accessDeniedTitle: 'Access Denied',
        accessDeniedDescCurrent: 'You are currently signed in as',
        accessDeniedDescRequired: 'and this panel is exclusively for',
        cannotAccessTitle: 'Unable to Access Panel',
        defaultError: 'An error occurred while verifying permissions or loading data.',
        loginBtn: 'Sign In',
        homeBtn: 'Back to Home',
        browseCoursesBtn: 'Browse Courses',
        switchAccountBtn: 'Switch Account (Logout)',
        retryBtn: 'Retry',
        roles: {
          admin: 'Administrators',
          teacher: 'Faculty Members & Instructors',
          student: 'Enrolled Students',
          superadmin: 'Super Admin'
        }
      }
    }
  }
};

function getInitialLocale(): Locale {
  if (typeof document !== 'undefined') {
    const saved = localStorage.getItem('codeera_locale');
    if (saved === 'en' || saved === 'ar') {
      return saved;
    }
    const htmlLang = document.documentElement.getAttribute('lang');
    if (htmlLang === 'en') return 'en';
  }
  return 'ar';
}

export const currentLocale = writable<Locale>('ar');

export function initLocale(): void {
  if (typeof document !== 'undefined') {
    const initial = getInitialLocale();
    currentLocale.set(initial);
    applyDocumentLocale(initial);
  }
}

export function setLocale(locale: Locale): void {
  currentLocale.set(locale);
  if (typeof document !== 'undefined') {
    try {
      localStorage.setItem('codeera_locale', locale);
    } catch (_) {}
    applyDocumentLocale(locale);
  }
}

export function toggleLocale(): void {
  currentLocale.update((prev) => {
    const next: Locale = prev === 'ar' ? 'en' : 'ar';
    if (typeof document !== 'undefined') {
      try {
        localStorage.setItem('codeera_locale', next);
      } catch (_) {}
      applyDocumentLocale(next);
    }
    return next;
  });
}

function applyDocumentLocale(locale: Locale): void {
  document.documentElement.setAttribute('lang', locale);
  document.documentElement.setAttribute('dir', locale === 'ar' ? 'rtl' : 'ltr');
  document.documentElement.classList.toggle('locale-ar', locale === 'ar');
  document.documentElement.classList.toggle('locale-en', locale === 'en');
}

export const t = derived(currentLocale, ($locale) => translations[$locale]);
export const currentHeroTranslations = derived(currentLocale, ($locale) => translations[$locale].hero);

export function formatPrice(cents: number, locale: Locale = 'ar'): string {
  const amount = (cents / 100).toLocaleString('en-US');
  if (locale === 'en') {
    return `${amount} EGP`;
  }
  return `${amount} ج.م`;
}

export function getLocalizedText(
  field: { ar?: string; en?: string } | string | undefined | null,
  locale: Locale = 'ar'
): string {
  if (!field) return '';
  if (typeof field === 'string') return field;
  if (locale === 'en') {
    return field.en || field.ar || '';
  }
  return field.ar || field.en || '';
}
