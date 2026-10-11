/**
 * Platform runtime & build configuration flags and re-exported pricing constants
 */
export * from './pricing.config';

export const SITE_ORIGIN = 'https://codeera.tech';
export const BRAND_NAME_DISPLAY = 'Codeera - كوديرا';
export const BRAND_DISCLAIMER_AR = 'Codeera منصة مستقلة وغير تابعة رسمياً لجامعة الأزهر.';

export const FEATURES = {
  /**
   * English toggle is disabled by default for the pilot launch due to
   * Arabic-first focus. Can be toggled on via VITE_ENABLE_LANGUAGE_TOGGLE=true.
   */
  ENABLE_LANGUAGE_TOGGLE: import.meta.env.VITE_ENABLE_LANGUAGE_TOGGLE === 'true'
};
