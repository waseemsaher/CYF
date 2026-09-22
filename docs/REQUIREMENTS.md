# Project Requirements — Al-Azhar FCAI Course Platform (MVP)

> Source of truth for WHAT to build. `AGENT.md` defines HOW to build it.
> If this file and any other instruction conflict, stop and ask the owner. Do not guess.

---

## 1. Overview

A course-selling platform for students of the Faculty of Computers & AI (FCAI), Al-Azhar University, Egypt.

- Students browse courses, pay manually (Vodafone Cash / other e-wallet / InstaPay) by uploading a payment screenshot, and get access after an admin approves.
- Course content is mostly **links** (lectures, quizzes, exams) that point to **closed Telegram groups**. There is **no video streaming service**.
- Access to the Telegram group is controlled automatically by a Telegram bot.
- MVP first, but the architecture must allow growth without rewrites.

**Non-goals for the MVP** (do NOT build): video hosting/streaming, online payment gateways, mobile apps, multi-university / multi-tenant support, coupon codes, drag-and-drop page builder, chat/comments, certificates, live sessions, push notifications, refunds automation.

---

## 2. Tech Stack (fixed)

| Layer | Choice |
|---|---|
| Backend | Laravel (latest stable), PHP 8.3+, REST JSON API under `/api/v1` |
| Frontend | SvelteKit (Svelte 5, TypeScript, SSR enabled), Tailwind CSS |
| Auth | Laravel Sanctum, cookie-based SPA auth (frontend and API on subdomains of the same parent domain) |
| Database | MySQL 8 (InnoDB, utf8mb4) |
| Cache / Queue / Sessions | Redis |
| File storage | DigitalOcean Spaces (S3-compatible), **private** bucket, access via signed URLs. Local disk in dev |
| Email | Laravel Mail via a free transactional provider (Brevo or Resend), always queued |
| Telegram | One bot, Bot API via webhook |
| Hosting | DigitalOcean (Droplet, Nginx, PHP-FPM, Supervisor for queue workers, Node for SvelteKit `adapter-node`) |
| Testing | Pest (backend), Vitest + Playwright (frontend, critical flows) |

Repo layout (monorepo):
```
/backend     Laravel API
/frontend    SvelteKit app
/docs        REQUIREMENTS.md, DECISIONS.md, API.md
AGENT.md
```

---

## 3. Roles & Permissions

Roles: `superadmin`, `admin` (assistant), `teacher`, `student`.

- **superadmin**: everything. Only role that can create/remove admins and change platform settings and revenue share.
- **admin (assistant)**: limited by granular permissions assigned by the superadmin (e.g. `payments.review`, `students.manage`, `courses.manage`, `content.manage`). Default assistant preset: payments review + students view.
- **teacher**: created only by superadmin/admin. No public registration. Scoped to assigned courses only.
- **student**: public registration. Sees only their own data.

Use a permissions package (e.g. `spatie/laravel-permission`). Every endpoint is protected by a Policy or permission check. No endpoint relies on hidden UI for security.

---

## 4. Registration & Profile (student)

Fields: full name, email, password, university branch (`azhar_boys` | `azhar_girls`), academic year (1st | 2nd), department (`CS`, `CY`, `Data Science`, `AI`), Telegram username (optional), phone/Telegram number (optional).

- Academic years and departments are **database tables** (admin-editable), not hardcoded lists. Branch is an enum.
- Email verification required before purchasing a course.
- Password reset via email.
- Profile page: edit name, year, department, Telegram link status, change password.
- Locale preference stored on the user.

Teacher accounts: admin enters name, email, temporary password, assigned courses. Teacher must change password on first login. Teachers can be assigned to one or many courses.

---

## 5. Domain Model

All money is stored as **integers in piasters (1 EGP = 100)**. Never floats/decimals in code.
All user-facing text fields that admins edit are **translatable** (`ar`, `en`) via JSON columns (e.g. `spatie/laravel-translatable`).
Use ULIDs or auto-increment IDs consistently; authorization must prevent IDOR regardless.
Soft deletes on: users, courses, course_items. Foreign keys and indexes on every relation from day one.

### Reference tables
- `academic_years` (id, name{ar,en}, sort_order)
- `departments` (id, code, name{ar,en}, sort_order)
- `terms` (id, name{ar,en}, starts_at, ends_at, is_current). Exactly one current term.

### Identity
- `users` (name, email unique, password, locale, branch enum, academic_year_id, department_id, telegram_user_id nullable unique, telegram_username nullable, phone nullable, email_verified_at, must_change_password bool, is_active, soft deletes)
- `telegram_link_tokens` (user_id, token hash, expires_at, used_at)

### Catalog
- `courses` (slug, title{ar,en}, description{ar,en}, cover_image_path, price_cents, status `draft|published|archived`, telegram_chat_id nullable, telegram_invite_link nullable, teacher_share_percent nullable override, sort_order, soft deletes). Free course = effective price 0.
- `course_audiences` (course_id, academic_year_id, department_id) — which year/department a course targets. No rows = visible to all.
- `course_teacher` (course_id, teacher_id, teacher_share_percent nullable override)
- `course_sections` (course_id, title{ar,en}, position)
- `course_items` (course_id, section_id, type `lecture_link|external_link|file|quiz|exam|text`, title{ar,en}, description{ar,en}, url nullable, file_path nullable, quiz_id nullable, position, is_published, soft deletes)

Initial seed courses: C++, Discrete Mathematics, Computing Fundamentals, Physics, English. All editable/addable from admin.

### Quizzes & exams (one engine)
- `quizzes` (course_id, kind `quiz|exam`, title, duration_minutes nullable, max_attempts, available_from nullable, available_until nullable, shuffle_questions, shuffle_options, results_visibility `immediate|after_close|hidden`)
- `questions` (quiz_id, type `mcq|true_false`, text{ar,en}, explanation{ar,en} nullable, points, position)
- `question_options` (question_id, text{ar,en}, is_correct)
- `quiz_attempts` (quiz_id, user_id, started_at, submitted_at, score, max_score, status `in_progress|submitted|expired`)
- `attempt_answers` (attempt_id, question_id, selected_option_ids JSON, is_correct, points_awarded)

### Pricing
- `discounts` (name, type `percent|fixed`, value, scope `all|courses`, starts_at, ends_at, is_active)
- `discount_course` (discount_id, course_id)
- **Rule:** discounts do not stack. The single best (lowest resulting price) valid discount applies. Effective price never below 0.

### Payments & enrollment
- `payments` (user_id, course_id, term_id, method, list_price_cents, discount_cents, amount_due_cents, sender_identifier, proof_path, proof_hash indexed, student_note nullable, status `pending|approved|rejected|cancelled`, rejection_reason nullable, reviewed_by, reviewed_at, teacher_share_percent, teacher_share_cents, platform_share_cents, created_at)
  - Price fields are **snapshotted at creation**. Share fields are **computed and frozen at approval**.
- `enrollments` (user_id, course_id, term_id, payment_id nullable, source `payment|free|admin_grant`, status `active|expired|revoked`, starts_at, expires_at, granted_by nullable). Unique (user_id, course_id, term_id).
- `teacher_payouts` (teacher_id, amount_cents, paid_at, note, created_by). Teacher balance = sum(teacher_share_cents of approved payments) − sum(payouts). MVP records payouts manually; no automatic transfers.

### Platform config
- `settings` (group, key, value JSON) — see §10.
- `content_blocks` (key, content{ar,en} JSON) — landing page text, FAQ, legal pages.
- `activity_log` (spatie/laravel-activitylog) — mandatory for payment review, enrollment grant/revoke, price/discount/setting/role changes.

---

## 6. Student Flow

1. Landing page → browse published courses (all visible publicly).
2. Register / login (verify email).
3. Course catalog defaults to courses matching the student's year+department, with a toggle to show all.
4. Course detail page: description, teacher(s), price (with discount shown), content outline (titles only, locked).
5. **Enroll:**
   - Effective price 0 → enrollment created immediately (`source=free`).
   - Otherwise → payment page (§7).
6. After approval: course page unlocks with sections and items (links, files, quizzes, exams), and the Telegram access box (§8).
7. Access lasts until the term's end (`expires_at = term.ends_at` + optional grace days from settings). Expired enrollments lose content access and are removed from the Telegram group.
8. Student can see own payments and their status, and own quiz results.

---

## 7. Payment Flow (manual)

Payment methods are managed in settings (label, type, number/handle, instructions{ar,en}, is_active). Seed: Vodafone Cash, InstaPay, "Other e-wallet".

Student steps:
1. Choose method → sees exact amount due and where to send.
2. Enters the sender wallet number / InstaPay handle.
3. Uploads proof image (jpg/png/webp, max size from settings, default 5 MB). Proof is **mandatory** for every method.
4. Submits → status `pending`. UI shows a clear "under review, usually short wait" message (ar/en).

Rules:
- One `pending` payment per (user, course, term). Others rejected with a clear error.
- Duplicate proof detection: store SHA-256 of the uploaded file; if the same hash exists on another payment, flag it prominently to the reviewer (do not auto-reject).
- Strip EXIF, validate real MIME type, re-encode image server-side.
- Proof stored in private storage; only reviewers can view via short-lived signed URL.
- Rejected → student sees the reason and can submit a new proof.

Admin review UI ("tasks" style queue):
- Default view: `pending` payments, oldest first, with counts per status.
- Each card: student (name, year, department, branch), course, term, amount due, sender identifier, proof image viewer (zoom), duplicate-proof warning, student's payment history.
- Actions: **Approve** / **Reject (reason required)**.
- Approve runs in one DB transaction with a row lock (idempotent): create enrollment, freeze revenue shares, log activity, queue email + Telegram notification.
- Manual override: admin can grant/extend/revoke an enrollment without a payment (`source=admin_grant`).

---

## 8. Telegram Integration

Goal: automatic, sharing-proof access to a **single closed Telegram group per course**.

Setup: the bot is an admin in each course group (permissions: invite users / ban users / approve join requests). Each course stores `telegram_chat_id` and a **join-request invite link** (`creates_join_request = true`).

Linking:
- Student clicks "Link Telegram" → deep link `https://t.me/<bot>?start=<one-time-token>`.
- Bot receives `/start <token>`, validates (single-use, expires in 15 min), stores `telegram_user_id` on the user, replies with confirmation.

Access:
- Enrolled + linked student sees the course's join link on the course page.
- When someone requests to join, bot receives `chat_join_request`. It **approves only if** that `telegram_user_id` belongs to a user with an `active` enrollment for that course (or is a teacher/admin of it). Otherwise **decline**.
- Scheduled job (daily, and on revoke): for expired/revoked enrollments, remove the member from the group (ban then unban so they can rejoin later after re-enrolling).

Other:
- Webhook endpoint protected by Telegram's secret token header. Idempotent handlers. Failures retried via queue.
- If the student hasn't linked Telegram, the course page shows clear linking instructions in the student's language.
- Bot sends a private message on approval/rejection when the account is linked.

---

## 9. Email

Provider: free transactional tier (Brevo / Resend). Sender domain configured with SPF/DKIM. **All mail is queued.**

Emails (ar/en by user locale): email verification, password reset, teacher account created (temp password), payment approved, payment rejected (with reason).
Mailable classes are templated with the brand identity. Failures logged and retried.
Design for provider limits (free tiers have daily caps): keep emails limited to the list above.

---

## 10. Admin-Configurable Without Code (MVP scope)

Stored in `settings` / `content_blocks`, editable from the admin dashboard, cached with invalidation on change:

- Payment methods (numbers, handles, instructions, active flag)
- Revenue share default (**teacher 70% / platform 30%**), with per-course and per-teacher-per-course overrides
- Upload limits (proof image max size; teacher file max size, default 50 MB; allowed extensions default: pdf, docx, pptx, xlsx, txt, png, jpg, webp; **block executables and archives by default**)
- Enrollment grace days after term end (default 0)
- Landing page content blocks: hero, "why us", how-it-works steps, FAQ, contact, footer, social links
- Legal pages: Terms of Use, Privacy Policy, Refund Policy (ar/en)
- Current term, academic years, departments
- Courses, prices, discounts, free courses
- Site name/logo/contact info

**Out of scope:** a visual page builder. Only the fields above are editable.

---

## 11. Dashboards

### Superadmin / admin
Sections gated by permission: Overview (pending payments count, active enrollments, revenue this term), **Payments queue**, Students (search, filter, view, enroll/revoke), Teachers (create, assign courses, share overrides), Courses (CRUD, audiences, Telegram group settings), Pricing & Discounts, Terms & Reference data, Teacher Payouts (balances + record payout), Content & Settings, Activity Log, Admin users & permissions (superadmin only).

### Teacher
My courses; manage sections/items (add links, upload files, create quizzes/exams and questions); view enrolled students (read only); view quiz/exam results per student and per question; view own earnings and payouts.

### Student
My courses, my payments (with status), profile.

---

## 12. Quizzes & Exams

- Question types: MCQ (single/multiple correct) and true/false only.
- Server-side timer is authoritative; auto-submit and grade on expiry.
- Attempt limits, availability window, shuffle options, per-quiz result visibility.
- Auto grading; scores stored per attempt and per answer.
- Students cannot view correct answers before the visibility rule allows.
- Teachers see results per student and per question (basic analytics: average, per-question correct rate).

---

## 13. Internationalization & RTL

- Languages: **Arabic (default, primary)** and English. URLs: Arabic at root, English under `/en`.
- Full RTL support: `dir="rtl"` on Arabic, Tailwind **logical properties** (`ms-`, `me-`, `ps-`, `pe-`, `text-start`), mirrored icons where directional.
- No hardcoded strings in components; all UI text in translation files. Arabic copy must read naturally (modern standard with light Egyptian-friendly tone), reviewed before merge.
- Backend messages/validation errors localized. API returns translatable fields in the requested locale (`Accept-Language`) or both when requested by admin endpoints.
- Digits: Western (0–9) throughout. Dates localized.
- Arabic typography quality is a **release blocker**: correct font rendering, line height, no broken ligatures, no clipped text.

---

## 14. Visual Identity & Design System

Palette (from owner):
- White `#FFFFFF`
- Storm Green `#0F282F` (RGB 15, 40, 47)
- Vivid Cyan `#02EFF0` (RGB 2, 239, 240)
- Brand gradient: `#0F282F → #02EFF0`

Guidelines:
- Dark storm-green surfaces with cyan accents for hero/header/footer; clean white surfaces for content.
- Cyan on white fails contrast for text: on white backgrounds use a darker cyan variant for text/links (derive one that passes **WCAG AA**, verify with a contrast checker) and keep vivid cyan for accents on dark backgrounds, buttons on dark, and gradients.
- Define all colors as design tokens (CSS variables / Tailwind theme). Derive a neutral scale and semantic colors (success, warning, error) from the palette.
- Font: **IBM Plex Sans Arabic** for Arabic (also covers Latin); fallback stack `system-ui, Tahoma, sans-serif`. Self-host fonts, `font-display: swap`.
- Logo: simple wordmark + icon placeholder; owner will supply final logo later. Make it a swappable asset.
- Reference for layout/inspiration: the owner's Figma Make design (E-Learning Platform Design) and `ahmed-elgohary.com`. Owner will provide screenshots; until then, follow this document and do not invent extra pages.
- Fully responsive (mobile-first: most students use phones). Support light theme only in MVP.
- Accessibility: keyboard navigation, visible focus, alt text, form labels/errors linked, AA contrast.

---

## 15. Pages (Frontend Routes)

Public: `/` landing (hero, why us, how it works, available courses, FAQ, footer), `/courses` (grid, filters by year/department), `/courses/[slug]`, `/login`, `/register`, `/forgot-password`, `/reset-password`, `/verify-email`, `/terms`, `/privacy`, `/refund-policy`.
Student (auth): `/dashboard`, `/my-courses`, `/my-courses/[slug]` (content + Telegram box), `/courses/[slug]/checkout`, `/payments`, `/quizzes/[id]` (take), `/quizzes/[id]/result`, `/profile`.
Teacher (auth): `/teacher`, `/teacher/courses/[id]` (content manager), `/teacher/courses/[id]/students`, `/teacher/quizzes/[id]` (builder + results), `/teacher/earnings`.
Admin (auth): `/admin` and sub-routes for each section in §11.
All `/en/...` mirrors.

---

## 16. Non-Functional Requirements

**Architecture / scalability**
- Modular monolith in Laravel organized by domain (Identity, Catalog, Learning, Payments, Enrollment, Telegram, Settings). Business logic in Action/Service classes, not controllers or models. No microservices.
- Stateless app servers (sessions, cache, queues in Redis) so more servers can be added behind a load balancer later.
- Heavy or external work (mail, Telegram calls, image processing) always on queues.
- Pagination on every list endpoint; no unbounded queries; eager loading to avoid N+1; indexes for all filters/sorts used.
- Expect **seasonal spikes** (term start, before exams): cache public course listings and settings; rate-limit sensitive endpoints.
- API versioned (`/api/v1`), consistent JSON error format, Laravel API Resources for all responses.

**Security**
- Policies on every resource; test for IDOR. Least privilege for teachers (own courses only).
- Rate limiting on login, registration, password reset, payment submission, Telegram webhook.
- Upload hardening (MIME sniffing, size limits, extension allowlist, private bucket, signed URLs, no direct public paths).
- HTTPS only, secure cookies, CSRF for Sanctum SPA, strict CORS to the frontend origin, security headers.
- Secrets only in `.env`; never committed. Mass-assignment protection. Output escaping.
- Audit trail for all money and access changes.

**Reliability / Ops**
- Automated daily DB backups (managed backups or `mysqldump` to Spaces) plus DO droplet backups. Documented restore steps.
- Queue workers supervised (Supervisor); failed jobs table monitored.
- Structured logging; error tracking hook (e.g. Sentry) optional.
- CI: lint, static analysis (Larastan level 6+), tests must pass before merge.
- Environments: local, staging (optional), production. Deployment script/documented steps for DigitalOcean.

**Performance targets (guideline)**: public pages fast on mobile 4G; API p95 under ~300 ms for typical reads under expected MVP load.

---

## 17. Business Rules Summary (assumptions the owner accepted or can change in settings)

- Teacher share **70%** of the amount actually paid (after discount); platform 30%. Editable globally and per course/teacher. Frozen per payment at approval time. Payouts recorded manually.
- One Telegram group per course; enrollment per (student, course, term).
- Access expires at term end (+ grace days). Re-purchase required for the next term.
- Discounts don't stack; best single discount applies.
- Free course = effective price 0 → instant enrollment.
- Email verification required to purchase.

---

## 18. Milestones (execute in order, one at a time)

0. **Foundation**: monorepo, Laravel + SvelteKit skeleton, Docker/dev env, CI, lint/test tooling, design tokens, i18n + RTL scaffold, DECISIONS.md.
1. **Identity**: register/login/verify/reset, roles & permissions, profile, teacher creation, reference tables + seeders.
2. **Catalog**: courses, audiences, terms, pricing & discounts, public pages (landing, courses, detail).
3. **Payments & Enrollment**: checkout, proof upload, admin review queue, approval flow, free courses, admin grants, activity log, emails.
4. **Telegram bot**: linking, join-request approval, expiry removal, notifications.
5. **Learning content**: sections/items, file uploads, student course page, quizzes/exams engine, results.
6. **Dashboards & configuration**: teacher dashboard, earnings/payouts, settings & content blocks, legal pages, assistant admin permissions.
7. **Polish**: Arabic/RTL QA, accessibility pass, performance/caching, security review.
8. **Deployment**: DigitalOcean setup, Nginx, Supervisor, backups, monitoring, launch checklist.

Each milestone ends with: passing tests, updated docs, a short summary of decisions and any open questions for the owner.

---

## 19. Open Items (do not block start; ask owner when reached)
- Final logo and any Figma screenshots.
- Final legal text review (drafts to be written in Arabic and English by the assistant, reviewed by the owner and ideally a lawyer).
- Domain name and email sender domain.
- Exact Telegram group setup steps per course (bot admin rights).