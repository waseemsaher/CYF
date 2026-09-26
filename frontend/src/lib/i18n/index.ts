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

export interface Translations {
  hero: HeroTranslations;
}

export const translations: Record<Locale, Translations> = {
  ar: {
    hero: {
      badge: 'المنصة التعليمية الرسمية لطلاب حاسبات الأزهر',
      titlePrefix: 'شروحات برمجية مركزة،',
      titleHighlight: 'خطوتك الواثقة نحو الامتياز الأكاديمي.',
      subheading: 'منصة Codeera توفر محتوى دراسي متخصص يغطي مناهج كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر، مع مجموعات تليجرام مقفولة ومتابعة مستمرة.',
      description: 'منصة Codeera توفر محتوى دراسي متخصص يغطي مناهج كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر، مع مجموعات تليجرام مقفولة ومتابعة مستمرة.',
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
        { value: '+10', label: 'مقررات تخصصية معتمدة' },
        { value: '100%', label: 'مجموعات تليجرام مقفولة' },
        { value: 'فوري', label: 'قبول آلي عبر البوت' }
      ],
      langToggleAria: 'التبديل إلى اللغة الإنجليزية',
      langToggleLabel: 'English'
    }
  },
  en: {
    hero: {
      badge: 'Official Platform for FCAI Al-Azhar Students',
      titlePrefix: 'Focused Tech Curriculum,',
      titleHighlight: 'Your Definitive Path to Academic Excellence.',
      subheading: 'Codeera provides specialized coursework tailored for Faculty of Computers & Artificial Intelligence Al-Azhar students, with private Telegram community groups and continuous academic support.',
      description: 'Codeera provides specialized coursework tailored for Faculty of Computers & Artificial Intelligence Al-Azhar students, with private Telegram community groups and continuous academic support.',
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
        { value: '+10', label: 'Accredited Tech Courses' },
        { value: '100%', label: 'Private Telegram Groups' },
        { value: 'Instant', label: 'Automated Bot Verification' }
      ],
      langToggleAria: 'Switch to Arabic',
      langToggleLabel: 'العربية'
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

export const currentHeroTranslations = derived(currentLocale, ($locale) => translations[$locale].hero);
