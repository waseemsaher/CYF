# Codeera (`codeera.tech` / `app.codeera.tech`) — Deployment & Hardening Runbook

This runbook covers deploying the marketing copy, SEO/GEO, admin-editable CTA system, and hardening updates to production safely without overwriting existing database content or breaking student workflows.

---

## 1. Pre-Deploy Checklist (Before Building / Deploying Frontend)

Open [`frontend/src/lib/pricing.config.ts`](./frontend/src/lib/pricing.config.ts) and review/confirm the following owner values:

1. **Manual Receipt Review Window (`REVIEW_TIME_TEXT`)**
   - Current placeholder: `'غالباً خلال [X] ساعات'`
   - Replace `[X]` with your real manual review window (e.g., `'غالباً خلال ساعتين إلى 4 ساعات'`).
   - *Safety net*: If left as `[X]`, `getSafeConfig()` automatically falls back to `'بنراجع الإيصال يدوياً وبنفتحلك المحتوى بعد الاعتماد'` so `[X]` never reaches students, and `npm run build` prints a console warning.
2. **Founder Story (`FOUNDER_TRUST_CONFIG.storyPlaceholder`)**
   - Replace the bracketed `[مساحة مخصصة لقصة مؤسس المنصة: ...]` text with your real 2–3 sentence story as a developer/founder.
   - *Safety net*: While square brackets `[...]` remain in `storyPlaceholder`, the founder story paragraph is automatically hidden on the homepage while keeping the rest of the trust card visible.
3. **Pricing, Bundle & Seasonal Offer (`COURSE_PRICE_EGP`, `BUNDLE_PRICE`, `EARLY_BIRD_PRICE`, `EARLY_BIRD_END_DATE`)**
   - Confirm `COURSE_PRICE_EGP` (default `300`), `BUNDLE_COURSES_COUNT` (default `3`), and `BUNDLE_PRICE` (default `750`).
   - Bundle UI only renders if `BUNDLE_PRICE > 0` and `BUNDLE_PRICE < COURSE_PRICE_EGP * BUNDLE_COURSES_COUNT`.
   - Early-bird UI only renders if `EARLY_BIRD_PRICE > 0`, `EARLY_BIRD_PRICE < COURSE_PRICE_EGP`, and `EARLY_BIRD_END_DATE` is a valid ISO date in the future (leave `''` to keep it hidden).
4. **Refund Policy Constants (`// TODO(OWNER): confirm refund policy values`)**
   - Confirm `REFUND_WINDOW_DAYS = 7`, `REFUND_MAX_WATCHED_PERCENT = 20`, and `REFUND_PROCESSING_DAYS_TEXT = '2 إلى 3 أيام عمل'` in `frontend/src/lib/pricing.config.ts`.
   - These constants act as the single source of truth across the homepage, course page, checkout, FAQ, `/refund`, and `/llms.txt`.
5. **WhatsApp Support Number**
   - Verify `WHATSAPP_SUPPORT_URL` in [`frontend/src/lib/socials.ts`](./frontend/src/lib/socials.ts) points to the active official support WhatsApp number.

---

## 2. Exact Production Commands (Backend)

Run these commands inside the `backend/` directory on the production server:

```bash
cd /path/to/codeera/backend

# 1. Run pending database migrations (creates `cta_blocks` table)
php artisan migrate --force

# 2. Seed ONLY missing default CTA slots (uses firstOrCreate — never overwrites existing rows)
php artisan db:seed --class=Database\\Seeders\\CtaBlockSeeder --force

# 3. Seed ONLY missing default ContentBlock rows (uses firstOrCreate — never overwrites existing admin edits)
php artisan db:seed --class=Database\\Seeders\\ContentBlockSeeder --force

# 4. Clear and warm framework & CTA caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### Optional: Force-Updating Specific Seeded Keys on Production
Both `ContentBlockSeeder` and `CtaBlockSeeder` use `firstOrCreate` by default so admin edits are **never** overwritten on re-seed. If you intentionally want to reset specific keys to the latest codebase defaults (for example, `legal.refund` and `landing.hero`), pass `SEED_FORCE_UPDATE_KEYS`:

```bash
# Overwrite ONLY specific content_blocks keys with codebase defaults:
SEED_FORCE_UPDATE_KEYS="legal.refund,landing.hero" php artisan db:seed --class=Database\\Seeders\\ContentBlockSeeder --force

# Overwrite ALL 6 content_blocks keys with codebase defaults (use only if you have not customized them in admin):
SEED_FORCE_UPDATE_KEYS="*" php artisan db:seed --class=Database\\Seeders\\ContentBlockSeeder --force
```

---

## 3. Post-Deploy Smoke Test Checklist (5 Minutes)

Run through these checks right after deployment:

1. **Mobile RTL Layout (360px – 414px viewport)**
   - Open `/`, `/courses`, `/courses/[slug]`, and `/courses/[slug]/checkout` on mobile (or Chrome DevTools mobile emulation at `375px` width).
   - Confirm RTL alignment, zero horizontal overflow, readable Arabic typography, and sticky/accessible primary CTAs.
2. **Full Student Payment & Enrollment Flow**
   - Register a test student account (`/register`) → complete mandatory Telegram linking (`/link-telegram`) → open a course checkout (`/courses/[slug]/checkout`) → enter wallet number, optional referral code (`CODEERA-TEST`), and upload a receipt screenshot.
   - Open `/admin/payments` as admin → verify `كود الترشيح: CODEERA-TEST` appears in its dedicated cyan badge separate from `ملاحظة الطالب` → click **موافقة** → confirm the course unlocks for the student.
3. **Admin CTA Management (`/admin/ctas`)**
   - Log in as admin and open `/admin/ctas`.
   - Verify each slot displays its behavior badge (`مطلوب: بيرجع للنص الافتراضي لو اتقفل` vs `اختياري: بيختفي لو اتقفل`).
   - Edit `home_hero_primary` label, save, and refresh `/` to confirm the new text appears.
   - Toggle `home_hero_primary` (required) **Off** → confirm `/` falls back to `"ابدأ بمادة واحدة"`.
   - Toggle `home_hero_secondary` (optional) **Off** → confirm the secondary button disappears completely on `/`.
4. **SEO, GEO & Structured Data Endpoints**
   - Visit `/sitemap.xml`, `/robots.txt`, and `/llms.txt` and verify all three return `200 OK` with clean output and zero `[X]` placeholders.
   - Paste the homepage URL and one `/courses/[slug]` URL into [Google Rich Results Test](https://search.google.com/test/rich-results) (or Schema Markup Validator) and verify `EducationalOrganization`, `WebSite`, `FAQPage`, `Course`, and `BreadcrumbList` JSON-LD schemas validate cleanly.
5. **Robots `noindex` on Private/Auth Routes**
   - View page source (or `<meta name="robots">`) on `/login`, `/register`, `/admin`, `/admin/ctas`, `/admin/payments`, `/dashboard`, `/payments`, and `/courses/[slug]/checkout` and confirm `<meta name="robots" content="noindex, nofollow">` is present.

---

## 4. Rollback Steps

If you need to roll back the release:

### A. Code Rollback
```bash
# Revert the merge commit on main without rewriting history
git checkout main
git revert -m 1 <merge-commit-sha>
git push origin main
```

### B. Database Migration Rollback (Only if rolling back `cta_blocks`)
The `cta_blocks` table (`2026_10_11_100000_create_cta_blocks_table.php`) is additive and does not alter any existing tables (`users`, `courses`, `payments`, `enrollments`, `content_blocks`).
- Leaving the `cta_blocks` table in place during a frontend rollback is completely safe.
- If you explicitly want to drop `cta_blocks`:
  ```bash
  cd /path/to/codeera/backend
  php artisan migrate:rollback --path=database/migrations/2026_10_11_100000_create_cta_blocks_table.php --force
  php artisan cache:clear
  ```
