import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
  isPlaceholder,
  getSafeConfig,
  NEUTRAL_REVIEW_FALLBACK_AR,
  REFUND_WINDOW_DAYS,
  REFUND_MAX_WATCHED_PERCENT,
  REFUND_PROCESSING_DAYS_TEXT,
  REFUND_GUARANTEE_SHORT,
  FOUNDER_TRUST_CONFIG,
  sanitizeReferralCode,
  parseReferralFromStudentNote
} from '../src/lib/pricing.config.ts';
import {
  DEFAULT_CTA_BLOCKS,
  CTA_SLOT_METADATA,
  REQUIRED_CTA_BEHAVIOR_LABEL_AR,
  OPTIONAL_CTA_BEHAVIOR_LABEL_AR,
  resolveCtaSlot,
  resolveOptionalCtaSlot,
  resolveAnnouncementBar
} from '../src/lib/api/ctas-core.ts';

test('isPlaceholder detects square-bracket placeholders and ignores clean strings', () => {
  assert.equal(isPlaceholder('غالباً خلال [X] ساعات'), true);
  assert.equal(isPlaceholder('[مساحة مخصصة لقصة المؤسس]'), true);
  assert.equal(isPlaceholder('غالباً خلال 2 إلى 4 ساعات'), false);
  assert.equal(isPlaceholder('طول الفصل الدراسي الحالي'), false);
  assert.equal(isPlaceholder(300), false);
});

test('getSafeConfig falls back when REVIEW_TIME_TEXT or founder story contain placeholders', () => {
  const defaultSafe = getSafeConfig();
  assert.equal(defaultSafe.reviewTimeText, NEUTRAL_REVIEW_FALLBACK_AR);
  assert.equal(defaultSafe.hasSpecificReviewTime, false);
  assert.equal(defaultSafe.founderStory, null);
  assert.ok(defaultSafe.placeholderWarnings.length >= 2);

  const customizedSafe = getSafeConfig({
    reviewTimeText: 'غالباً خلال 2 إلى 4 ساعات',
    founderStory: 'مطور خريج حاسبات الأزهر قرر يجمع شروحات المواد في مكان واحد منظم.'
  });
  assert.equal(customizedSafe.reviewTimeText, 'غالباً خلال 2 إلى 4 ساعات');
  assert.equal(customizedSafe.hasSpecificReviewTime, true);
  assert.equal(
    customizedSafe.founderStory,
    'مطور خريج حاسبات الأزهر قرر يجمع شروحات المواد في مكان واحد منظم.'
  );
});

test('getSafeConfig validates BUNDLE_PRICE and EARLY_BIRD_PRICE / EARLY_BIRD_END_DATE', () => {
  // Valid bundle: 750 < 300 * 3 (900)
  const validBundle = getSafeConfig({
    coursePriceEgp: 300,
    bundleCoursesCount: 3,
    bundlePrice: 750
  });
  assert.equal(validBundle.isBundleValid, true);
  assert.equal(validBundle.bundleSavingEgp, 150);

  // Invalid bundle: price >= separate total or <= 0
  const invalidBundleHigh = getSafeConfig({
    coursePriceEgp: 300,
    bundleCoursesCount: 3,
    bundlePrice: 900
  });
  assert.equal(invalidBundleHigh.isBundleValid, false);
  assert.equal(invalidBundleHigh.bundleSavingEgp, 0);

  const invalidBundleZero = getSafeConfig({
    coursePriceEgp: 300,
    bundleCoursesCount: 3,
    bundlePrice: 0
  });
  assert.equal(invalidBundleZero.isBundleValid, false);

  // Early-bird: valid only when price < coursePrice AND endDate is in the future
  const now = new Date('2026-10-11T10:00:00Z');
  const activeEarlyBird = getSafeConfig({
    coursePriceEgp: 300,
    earlyBirdPrice: 250,
    earlyBirdEndDate: '2026-11-15T23:59:59Z',
    now
  });
  assert.equal(activeEarlyBird.isEarlyBirdValid, true);
  assert.ok(activeEarlyBird.earlyBirdDateLabel.length > 0);

  const expiredEarlyBird = getSafeConfig({
    coursePriceEgp: 300,
    earlyBirdPrice: 250,
    earlyBirdEndDate: '2026-10-01T00:00:00Z',
    now
  });
  assert.equal(expiredEarlyBird.isEarlyBirdValid, false);

  const placeholderEarlyBird = getSafeConfig({
    coursePriceEgp: 300,
    earlyBirdPrice: 250,
    earlyBirdEndDate: '[YYYY-MM-DD]',
    now
  });
  assert.equal(placeholderEarlyBird.isEarlyBirdValid, false);
});

test('refund policy constants act as single source of truth across derived strings', () => {
  assert.equal(REFUND_WINDOW_DAYS, 7);
  assert.equal(REFUND_MAX_WATCHED_PERCENT, 20);
  assert.equal(REFUND_PROCESSING_DAYS_TEXT, '2 إلى 3 أيام عمل');
  assert.ok(REFUND_GUARANTEE_SHORT.includes(String(REFUND_WINDOW_DAYS)));
  assert.ok(REFUND_GUARANTEE_SHORT.includes(`${REFUND_MAX_WATCHED_PERCENT}%`));
  assert.ok(FOUNDER_TRUST_CONFIG.refundSummary.includes(String(REFUND_WINDOW_DAYS)));
  assert.ok(FOUNDER_TRUST_CONFIG.refundSummary.includes(`${REFUND_MAX_WATCHED_PERCENT}%`));
});

test('sanitizeReferralCode and parseReferralFromStudentNote sanitize and extract referral codes safely', () => {
  assert.equal(
    sanitizeReferralCode('  <script>alert(1)</script>[CODEERA-2026]  '),
    'alert(1)CODEERA-2026'
  );
  assert.equal(sanitizeReferralCode('A'.repeat(60)).length, 32);

  const parsedBoth = parseReferralFromStudentNote(
    '[كود ترشيح: AZHAR-CS50] — مشترك في باقة 3 مواد'
  );
  assert.equal(parsedBoth.referralCode, 'AZHAR-CS50');
  assert.equal(parsedBoth.cleanNote, 'مشترك في باقة 3 مواد');

  const parsedCodeOnly = parseReferralFromStudentNote('[كود ترشيح: FRIEND10]');
  assert.equal(parsedCodeOnly.referralCode, 'FRIEND10');
  assert.equal(parsedCodeOnly.cleanNote, '');

  const parsedNoteOnly = parseReferralFromStudentNote('حولت من رقم والدي');
  assert.equal(parsedNoteOnly.referralCode, null);
  assert.equal(parsedNoteOnly.cleanNote, 'حولت من رقم والدي');
});

test('CTA slots distinguish required vs optional behavior and prevent broken #sample-lesson links', () => {
  // Verify metadata and required flags
  assert.equal(DEFAULT_CTA_BLOCKS.home_hero_primary.required, true);
  assert.equal(DEFAULT_CTA_BLOCKS.home_bottom.required, true);
  assert.equal(DEFAULT_CTA_BLOCKS.course_page_default.required, true);
  assert.equal(DEFAULT_CTA_BLOCKS.home_hero_secondary.required, false);
  assert.equal(DEFAULT_CTA_BLOCKS.announcement_bar.required, false);

  assert.equal(CTA_SLOT_METADATA.home_hero_primary.behaviorLabelAr, REQUIRED_CTA_BEHAVIOR_LABEL_AR);
  assert.equal(CTA_SLOT_METADATA.home_hero_secondary.behaviorLabelAr, OPTIONAL_CTA_BEHAVIOR_LABEL_AR);

  // Required slot falls back to default when deactivated
  const deactivatedPrimaryMap = {
    home_hero_primary: {
      ...DEFAULT_CTA_BLOCKS.home_hero_primary,
      label_ar: 'نص مخصص',
      is_active: false
    }
  };
  const resolvedPrimary = resolveCtaSlot(deactivatedPrimaryMap, 'home_hero_primary');
  assert.equal(resolvedPrimary.label_ar, 'ابدأ بمادة واحدة');
  assert.equal(resolvedPrimary.url, '/courses');

  // Optional slot (home_hero_secondary) returns null when deactivated
  const deactivatedSecondaryMap = {
    home_hero_secondary: {
      ...DEFAULT_CTA_BLOCKS.home_hero_secondary,
      url: '/courses',
      is_active: false
    }
  };
  assert.equal(
    resolveOptionalCtaSlot(deactivatedSecondaryMap, 'home_hero_secondary', {
      hasSampleLessons: true
    }),
    null
  );

  // Optional slot (home_hero_secondary) returns null when pointing to #sample-lesson and hasSampleLessons is false
  assert.equal(
    resolveOptionalCtaSlot(DEFAULT_CTA_BLOCKS, 'home_hero_secondary', {
      hasSampleLessons: false
    }),
    null
  );

  // Optional slot (home_hero_secondary) renders when pointing to #sample-lesson and hasSampleLessons is true
  const visibleSampleCta = resolveOptionalCtaSlot(DEFAULT_CTA_BLOCKS, 'home_hero_secondary', {
    hasSampleLessons: true
  });
  assert.notEqual(visibleSampleCta, null);
  assert.equal(visibleSampleCta?.url, '#sample-lesson');

  // Optional slot (home_hero_secondary) renders when admin points it to another valid URL even if hasSampleLessons is false
  const customSecondaryMap = {
    home_hero_secondary: {
      ...DEFAULT_CTA_BLOCKS.home_hero_secondary,
      label_ar: 'تواصل معنا على واتساب',
      url: 'https://wa.me/201555444726',
      is_active: true
    }
  };
  const visibleCustomCta = resolveOptionalCtaSlot(customSecondaryMap, 'home_hero_secondary', {
    hasSampleLessons: false
  });
  assert.notEqual(visibleCustomCta, null);
  assert.equal(visibleCustomCta?.url, 'https://wa.me/201555444726');

  // Announcement bar is hidden by default and when expired
  assert.equal(resolveAnnouncementBar(DEFAULT_CTA_BLOCKS), null);
});
