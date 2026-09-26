# Foundation Decisions

## Scope and milestone
- Milestone 0 is the foundation only: repo skeleton, tooling, config, and project conventions.
- No business features or milestone 1 flows are included in this milestone.

## Tooling decisions
- Backend: Laravel 13.x with PHP 8.3.x.
- Frontend: SvelteKit + TypeScript + Tailwind CSS.
- Local runtime: Docker-first workflow for both app layers, with Laravel and SvelteKit containers defined in the repo.
- Package management: Composer for PHP, npm for frontend.
- Testing: Pest for backend; Vitest + Playwright for frontend checks.
- Static analysis: Larastan (minimum level 6) and Svelte type checking.

## Architecture decisions
- Monorepo layout stays exactly as required: /backend, /frontend, /docs.
- Domain folders in the Laravel app will follow Identity, Catalog, Learning, Payments, Enrollment, Telegram, Settings under app/Domain.
- The API remains versioned under /api/v1 once the first real endpoints are added.
- HTTP controllers stay thin; business logic moves to actions/services.

## Global quality gates
- No hardcoded user-facing strings in the frontend.
- Arabic remains the default locale and the app must support RTL/LTR from the start.
- All docs and decisions are kept in /docs.
- Work remains limited to the active milestone until the next milestone is approved.

## Catalog bootstrap
- `CatalogSeeder` owns the initial academic years, departments, current term, and five required courses.
- Catalog seed records use `updateOrCreate` so `migrate:fresh --seed` and repeated seeding remain deterministic and do not duplicate reference data.

---

# Milestone 3 Decisions

## Payment flow
- Proof images are processed server-side using PHP GD (no Intervention Image dependency needed). EXIF is stripped by re-encoding to JPEG. Images stored in private local disk with randomized filenames.
- SHA-256 hash of proof files is stored and indexed for duplicate detection. Duplicates are flagged in the admin UI but not auto-rejected per requirements.
- Revenue share (teacher/platform split) is frozen at payment approval time inside a DB transaction with pessimistic row locking. Share priority: per-course override > global default (70/30).
- ApprovePayment action is idempotent: re-approving an already-approved payment returns the existing enrollment without creating a duplicate.

## Enrollment
- Free courses (effective price = 0 after discounts) create an enrollment immediately without a payment record, per requirements §6.
- Admin can grant enrollments without payment (source=admin_grant), revoke enrollments, and extend expiry dates. All logged via spatie/activitylog.

## Settings
- Platform settings stored in a `settings` table with (group, key, value JSON) pattern. Reads are cached with 1-hour TTL and invalidated on writes.
- Default settings seeded: payment methods (Vodafone Cash, InstaPay, Other e-wallet), revenue share (70%), upload limits (5MB proof, 50MB teacher files), grace days (0).

## Email
- PaymentApproved and PaymentRejected mailables are queued (ShouldQueue). Subject and body are locale-aware (ar/en based on user locale preference).
- Email provider is configured via SMTP env vars (supports Brevo, Resend, or any SMTP provider).

## Activity logging
- `spatie/laravel-activitylog` installed. Logs cover: payment approval, rejection, cancellation, enrollment grant, revoke, and extension.

## Frontend
- Checkout, payments list, and admin review queue pages use client-side authenticated fetch (credentials: include) since they require Sanctum cookies. Public pages continue to use SSR server load functions.
- The admin payment queue uses status tabs with badge counts and inline rejection reason form.

---

# Milestone 4 Decisions

## Telegram bot integration
- Bot interaction uses a lightweight, typed `TelegramClient` wrapping Laravel's HTTP client with 10s timeouts. Supports full test mocking via `Http::fake()`.
- Bot deep linking generates a cryptographically secure 32-character token stored with SHA-256 hash in `telegram_link_tokens` with 15-minute expiration. Previous unused tokens for the same user are deleted on new token generation.
- `/start <token>` in Telegram links the user's account by matching the SHA-256 hash. If another account was previously linked to that Telegram ID, it is unlinked automatically.
- Webhook endpoint (`POST /api/v1/telegram/webhook`) verifies `X-Telegram-Bot-Api-Secret-Token` header. Rejects unauthorized calls with 403. Rate-limited to 60 req/min.
- Join request approvals (`chat_join_request`): automatically approves requests if the Telegram user ID belongs to a platform user with an active enrollment for that course (or is staff/admin). All others are declined.
- Member removal on enrollment expiration or revocation: uses Telegram's `banChatMember` followed immediately by `unbanChatMember` (with `only_if_banned: true`), removing them from the closed group without blacklisting so they can rejoin upon re-enrollment.
- Scheduled task `telegram:remove-expired` runs daily to check and kick expired/revoked members from course chats.
- Payment notifications: `SendTelegramNotificationJob` dispatches queued notifications to the student's linked Telegram upon payment approval or rejection.

---

# Milestone 5 Decisions

## Learning Content & Course Outline
- Content access is gated by active enrollment: unauthenticated users or non-enrolled students see the course syllabus/outline with `is_locked: true` and sensitive URLs/files stripped out. Enrolled students and staff unlock full lecture links, private downloads, quizzes, and the closed Telegram group invite link.
- Course files are stored on the private `local` storage disk (`course_files/{course_id}/...`) and downloaded exclusively through the authorized streaming endpoint `GET /api/v1/courses/{slug}/items/{item_id}/file` with MIME validation and 50MB size limit.

## Quizzes & Exams Engine
- Single unified engine for both `quiz` and `exam` models. Supports duration limits (timed tests), max attempt limits, date availability windows, question shuffling, and option shuffling.
- Security: `StartQuizAttempt` strips `is_correct` flags from question options so answers are never exposed to the client prior to or during an attempt.
- Auto-grading: `GradeQuizAttempt` grades submissions synchronously, awards question points, logs `attempt_answers`, and marks the attempt `submitted`. Attempts exceeding the time limit (+1 min network grace) are marked `expired`.
- Results visibility: `immediate` displays score and full breakdown with model answers and explanations; `after_close` displays the score but hides answers until `available_until` elapses; `hidden` hides the score and answers from the student.

---

# Milestone 6 Decisions

## Dashboards & Operations
- Admin overview dashboard surfaces real-time metrics: pending payments, active enrollments, current term revenue, and registered student count with Spatie activity log integration.
- Assistant admin permissions: granular permissions (`payments.review`, `students.manage`, `courses.manage`, `content.manage`) allow assistant admins to perform designated tasks without full superadmin rights.

## Teacher Portal & Payouts
- Teacher earnings balance is dynamically computed: sum of frozen `teacher_share_cents` from approved payments in assigned courses minus recorded manual payouts from `teacher_payouts`.
- Manual payouts record timestamp, amount, admin author, and optional notes; all operations logged via activitylog.
- Teachers access read-only student rosters and quiz analytics (student scores, average accuracy, per-question correct rates) for their assigned courses only.

## Platform Configuration & Content Blocks
- Content blocks stored in `content_blocks` table with multi-lingual JSON `{ar, en}` content.
- Reads cached with 1-hour TTL via `ContentBlockService` and automatically invalidated upon update.
- Seeded blocks support landing page elements and legal policies (Terms, Privacy, Refund).

---

# Milestone 7 Decisions

## Security Review & Hardening
- Rate limiting middleware configured: `/api/v1/login` (10 req/min), `/api/v1/register` (10 req/min), and `/api/v1/payments` submission (10 req/min). Telegram webhook throttled at 60 req/min.
- Standard defensive HTTP security headers injected via `SecurityHeaders` middleware:
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `X-XSS-Protection: 1; mode=block`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- Strict CORS configuration via `config/cors.php` restricting allowed origins to configured frontend URL (`FRONTEND_URL`) and localhost dev origins, with credentials support enabled.
- Authorization isolation: explicit 403 Forbidden checks implemented for cross-student quiz attempts, payments, and teacher course boundary enforcement.

## Performance & Static Analysis
- Static analysis configured with `phpstan.neon` loading `vendor/larastan/larastan/extension.neon` at Level 6. All models, actions, and controllers typed and passing with 0 errors.
- HTTP caching headers: `Cache-Control: public, max-age=3600, stale-while-revalidate=86400` applied to public reference data (academic years, departments, terms) and public content blocks.
- `Model::preventLazyLoading` enforced across application to guarantee eager loading and prevent N+1 query regressions.

## Frontend Polish & Accessibility Pass
- Root layout (`+layout.svelte`) introduces universal, responsive header and footer navigation, skip-to-content accessibility link (`#main-content`), and centralized design token definitions.
- Typography: Google Fonts `IBM Plex Sans Arabic` preconnected and imported in `app.html` for Arabic typography across all platforms.
- RTL correctness: verified all views use logical utilities (`start`, `end`, `margin-inline`, `padding-inline`) and mirrored directional icons (`→` back, `←` forward in RTL context).
- Form accessibility: all inputs feature associated label tags, focus rings with high-contrast outlines, and ARIA roles for errors and loading indicators.

---

# Milestone 8 Decisions

## Deployment & Hosting Architecture
- DigitalOcean Droplet (Ubuntu 24.04 LTS, 2GB-4GB RAM, 2 vCPUs) selected for hosting PHP-FPM 8.3, Node.js SvelteKit SSR, MySQL 8.4, and Redis 7.
- Nginx configured as reverse proxy with HTTP/2 and Let's Encrypt SSL, routing `/api/*`, `/up`, and `/sanctum/*` to PHP-FPM (`unix:/run/php/php8.3-fpm.sock`) and all frontend routes to SvelteKit Node SSR (`127.0.0.1:3000`).
- SvelteKit static immutable assets (`/_app/immutable/`) served directly by Nginx with 1-year cache headers (`Cache-Control: public, max-age=31536000, immutable`).
- `client_max_body_size` set to 55M across Nginx and PHP to handle 50MB course material uploads and proof images.

## Process Supervision & Scheduler
- Supervisor manages 2 background queue workers (`cyf-worker`) running `php artisan queue:work redis` with graceful termination, memory limits, and auto-restart.
- SvelteKit SSR cluster managed via PM2 (`ecosystem.config.cjs`) or Supervisor (`cyf-frontend.conf`).
- System cron configured to execute `php artisan schedule:run` every minute for Telegram expired member kick job and quiz closure automation.

## Backups & Automation
- Automated daily database backup (`scripts/backup.sh`) dumps MySQL using `--single-transaction`, compresses with gzip, retains the last 7 days locally, and syncs to DigitalOcean Spaces bucket.
- Safe restore script (`scripts/restore.sh`) with validation and application cache clearing.
- Zero-downtime deployment script (`scripts/deploy.sh`) with maintenance bypass token, asset compilation, migration enforcement, and health check validation.
- Containerized alternative provided via `docker-compose.prod.yml`.

---

# Landing Page Hero Section Decisions

## Particle Network Canvas Architecture
- **Rendering & Tech**: Self-contained Svelte component (`src/lib/components/HeroCanvas.svelte`) using Plain HTML5 Canvas 2D context with zero external animation dependencies. Text and interactive DOM elements are server-rendered immediately, and canvas initializes asynchronously on mount to eliminate any impact on First Contentful Paint.
- **Design Tokens**: Background color and particle/connection line stroke colors are dynamically read from root CSS custom properties (`--storm-green` / `#0F282F`, `--vivid-cyan-rgb` / `2, 239, 240`), ensuring zero hardcoded hex values in canvas rendering logic.
- **Particle Count & Scale**: Tiered responsive particle count to preserve 60fps on mid-range and low-end mobile devices without degrading Lighthouse performance:
  - Mobile (< 640px): 38 particles, connection distance threshold 85px.
  - Tablet (640px – 1024px): 52 particles, distance threshold 105px.
  - Desktop (> 1024px): 68 particles, distance threshold 125px.
  - Performance optimization: squared-distance check (`dx*dx + dy*dy < maxDist*maxDist`) pre-filters non-connecting pairs prior to performing `Math.sqrt`.
- **Line Opacity & Desktop Interaction**: Base connection alpha computed as `(1 - dist / maxDist) * 0.32`. On desktop, pointer coordinates tracked with a 140px proximity radius, applying subtle brightening (+0.28 alpha boost capped at 0.75, +1.5px particle radius expansion) without distracting from foreground readability.
- **Motion & Battery Preservation**:
  - `prefers-reduced-motion: reduce`: animation loop (`requestAnimationFrame`) is bypassed; a single static frame is rendered on mount/resize.
  - `document.visibilityState`: tab switching immediately cancels `requestAnimationFrame` and cleanly resumes on visibility restore.
  - Device pixel ratio capped at 2 (`Math.min(window.devicePixelRatio || 1, 2)`) to protect GPU fill-rate on high-DPI displays.

## Typewriter Effect & Internationalization (i18n)
- **Component**: Modular `src/lib/components/HeroTypewriter.svelte` cycling through 4 academic track phrases loaded from `src/lib/i18n/index.ts`.
- **Timing Parameters**: Typing speed 75ms/char, phrase completion dwell 2200ms, backspace delete speed 38ms/char, phrase turnaround delay 350ms.
- **Reduced Motion**: Directly falls back to statically displaying the primary phrase with the blinking cursor disabled.
- **Bilingual & RTL/LTR**: First-class Arabic (default) and English support with logical CSS/Tailwind utilities (`margin-inline`, `padding-inline`, `text-start`, mirrored SVG arrow icon via `transform: scaleX(-1)` in RTL).

## Full-Bleed Hero & Background Video Extension Decisions
- **Full-Bleed Viewport Layout**:
  - Hero container updated to `min-height: 100dvh` (accommodating mobile dynamic browser chrome), `width: 100%`, `margin: 0`, and `border-radius: 0; border: none; box-shadow: none;` on both desktop and mobile viewports.
  - Video and canvas fill this full-bleed container edge-to-edge.
  - Content elements (badge, heading, typewriter, description, CTA buttons, stats glass bar) remain centered within a `max-width: 860px` container with responsive padding (`clamp(4rem, 8vw, 6rem) clamp(1.5rem, 5vw, 3.5rem)`).
- **Background Video Layer & Fallback Poster**:
  - Video element (`<video autoplay muted loop playsinline poster="/images/hero-poster.jpg">`) points to `/videos/hero-bg.mp4`.
  - Conditioned with `shouldLoadVideo()`: omitted when `prefers-reduced-motion: reduce`, viewports < 768px, or on slow data connections (`navigator.connection?.saveData` or `effectiveType` of `2g` / `slow-2g`). In those cases, `/images/hero-poster.jpg` is served directly without consuming video bandwidth.
  - **Poster Frame Production**: Extracted from frame at 1-second mark of `hero-bg.mp4` (resolution: 2560x1080) using OpenCV at 92% JPEG quality to guarantee instantaneous visual fill before video playback begins. Owner may replace with custom still if desired.
- **Transparent Canvas Particle Network & Desktop Mouse Interaction**:
  - Canvas background switched to `ctx.clearRect(0, 0, width, height)` (completely transparent), allowing the video layer to show through.
  - Particle and connection line base alpha reduced (`0.16` base line alpha, `0.30` base dot alpha) to provide subtle texture without competing with video content.
  - Desktop mouse interaction tracked via section `mousemove` listener with pre-cached bounding offsets (zero DOM queries per event/frame). When pointer is fine (desktop), lines and dots within a 160px radius visibly brighten (line alpha boosted up to `0.85`, particle radius expanded by up to `+1.8px`, alpha up to `1.0`).
- **Color Identity Harmonization with New Platform Palette**:
  - Re-aligned hero surface and components with the new 7-color palette (`#E5E2DD`, `#FAF8F5`, `#D5CBC1`, `#1A1918`, `#6B6864`, `#2A3B6A`, `#C82B34`).
  - Dark overlay updated to deep navy (`rgba(30, 43, 77, 0.78)`) fading into charcoal (`rgba(26, 25, 24, 0.92)`).
  - Canvas particles render in warm secondary tone (`#D5CBC1`) and dynamically brighten on cursor proximity to rich crimson accent (`#C82B34`).
  - Primary CTA styled with Brand Crimson (`#C82B34`) and pure white text (`#FFFFFF`) with 4.89:1 AA contrast ratio.
  - Secondary CTA styled with translucent Navy (`rgba(42, 59, 106, 0.4)`) and warm white text (`#FAF8F5`) with >12:1 AAA contrast ratio.
  - Typewriter badge and stats ribbon harmonized with translucent charcoal (`#1A1918`), crimson badge accents, and warm card white typography.

- **Compliance & Copy Rectification**:
  - Removed "official" / "الرسمية" and university product affiliation claims from hero badges across Arabic and English locales. Reworded to "منصة تعليمية لطلاب حاسبات الأزهر" / "Educational Platform for FCAI Al-Azhar Students".
  - Normalized stats copy to "مقررات تخصصية متكاملة" / "Comprehensive Tech Courses" to eliminate implied external accreditation.


