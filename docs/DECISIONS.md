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

## Content Blocks & Platform Configuration
- Content blocks (`content_blocks` table) manage landing page copy and legal policies (Terms of Use, Privacy Policy, Refund Policy).
- Reads are cached with 1-hour TTL and invalidated automatically upon admin updates.
