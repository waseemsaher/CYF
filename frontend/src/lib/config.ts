/**
 * Platform runtime & build configuration flags
 */
export const FEATURES = {
  /**
   * English toggle is disabled by default for the pilot launch due to
   * incomplete translations across student critical path routes.
   * Can be toggled on via environment variable VITE_ENABLE_LANGUAGE_TOGGLE=true.
   */
  ENABLE_LANGUAGE_TOGGLE: import.meta.env.VITE_ENABLE_LANGUAGE_TOGGLE === 'true',
};
