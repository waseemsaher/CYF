# Codeera (`codeera.tech` / `app.codeera.tech`) — Deployment & Hardening Runbook

This runbook covers deploying the marketing copy, SEO/GEO, admin-editable CTA system, and hardening updates to production safely without overwriting existing database content or breaking student workflows.

---

## 1. Pre-Deploy Checklist (Optional Owner Content)

All core business rules are already confirmed and configured in [`frontend/src/lib/pricing.config.ts`](./frontend/src/lib/pricing.config.ts):
- `REVIEW_TIME_TEXT = 'غالباً خلال ساعات'`
- `REFUND_WINDOW_DAYS = 7` (no watched-percentage condition) and `REFUND_PROCESSING_DAYS_TEXT = '2 إلى 3 أيام عمل'`
- `SUBSCRIPTION_PLANS.semester` (`'لحد نهاية الفصل الدراسي الحالي'`)
- `COURSE_PRICE_EGP = 300`, `BUNDLE_COURSES_COUNT = 3`, `BUNDLE_PRICE = 750`

Only two optional items remain in [`frontend/src/lib/pricing.config.ts`](./frontend/src/lib/pricing.config.ts) when you are ready to fill them:

1. **Write Founder Story (`FOUNDER_TRUST_CONFIG.storyAr`)** *(optional)*
   - Fill `storyAr` (2–4 honest sentences following the 3-line guide comment above `FOUNDER_TRUST_CONFIG`), plus `founderName` and `photoUrl` if desired.
   - *Safety net*: While `storyAr` is empty (`''`), the story paragraph is hidden automatically on the homepage while keeping the rest of the trust card visible.
2. **Set Early-Bird Date/Price Before Exams (`EARLY_BIRD_PRICE` / `EARLY_BIRD_END_DATE`)** *(optional)*
   - Currently `EARLY_BIRD_PRICE = 0` and `EARLY_BIRD_END_DATE = ''` (offer hidden).
   - If you run a pre-midterm offer later, set `EARLY_BIRD_PRICE < COURSE_PRICE_EGP` and `EARLY_BIRD_END_DATE` to a future ISO date (`'YYYY-MM-DD'`).

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

### Refreshing old content blocks on production

> **Warning:** Before overwriting any existing `content_blocks` rows in production, **back up your production database** and copy any manually edited content blocks from the admin panel (or inspect them using the command below). By default, `ContentBlockSeeder` uses `firstOrCreate` so existing rows are untouched unless you pass `SEED_FORCE_UPDATE_KEYS`.

1. **Inspect current `content_blocks` values in production before overwriting (one-liner):**
   ```bash
   php artisan tinker --execute="App\Models\ContentBlock::all(['key','content'])->each(fn(\$b) => dump(\$b->key, \$b->getTranslations('content')));"
   ```

2. **Force-update ONLY the 6 changed content block keys with the new owner-approved copy:**
   ```bash
   SEED_FORCE_UPDATE_KEYS="landing.hero,landing.why_us,landing.faq,legal.terms,legal.privacy,legal.refund" php artisan db:seed --class=Database\\Seeders\\ContentBlockSeeder --force
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

