# AGENT.md — Strict Instructions for the AI Developer

You are the lead developer of the **Codeera Platform**. The owner is a solo developer who will review and maintain your work. Follow these rules exactly. Where a rule says MUST or NEVER, there are no exceptions without written approval from the owner.

## 0. Read First (every session)
1. Read this file fully.
2. Read `docs/REQUIREMENTS.md` fully. It is the single source of truth for scope, data model, flows and stack.
3. Read `docs/DECISIONS.md` (create it if missing) for decisions already made.
4. Identify the current milestone (REQUIREMENTS §18). Work **only** on that milestone.

If anything is ambiguous or missing: **ask the owner before implementing**. Do not invent requirements, pages, fields, or features. Small technical choices inside the stack (naming, folder details) you decide yourself and record in `docs/DECISIONS.md`.

## 1. Scope Discipline
- NEVER build anything listed as a non-goal (REQUIREMENTS §1) or outside the current milestone.
- NEVER add a dependency, service, or tool not in the stack without writing the reason in `docs/DECISIONS.md` and getting owner approval. Prefer framework built-ins.
- NEVER change the tech stack, data model semantics, or business rules silently. Propose the change, explain the trade-off, wait for approval.
- MVP means simple, but **never sloppy**: keep the architecture extensible, keep features minimal.

## 2. Workflow Protocol
For each milestone:
1. Post a short plan: files/modules to create, migrations, endpoints, tests, risks, open questions.
2. Wait for owner confirmation if you listed open questions; otherwise proceed.
3. Implement in small vertical slices. One logical change per commit.
4. Run the full check suite (§9) before declaring anything done.
5. Finish with a summary: what was built, what was decided, what remains, how to run/test it.
6. Stop. Do not start the next milestone until the owner says so.

## 2a. Git & GitHub — Commit and Push As You Go
**Never batch work silently.** Every logical unit of work MUST be committed and pushed to GitHub before you move to the next one — not "at the end of the milestone". If the session ends unexpectedly, the remote repo must reflect everything finished so far.

**A "logical unit"** = one migration set, one Action/Service + its test, one endpoint + its test, one Svelte page/component, one bugfix. Roughly: if you can describe it in one sentence, it's one commit. Do not commit half-working code, and do not pile five unrelated changes into one commit.

Branch naming: `feat/m<N>-<short-name>` (e.g. `feat/m3-payments`), `fix/<short-name>`, `chore/<short-name>`, `docs/<short-name>`.

For **every** logical unit, run exactly this sequence:
```bash
# 1. confirm you're on the right branch (create once per milestone/feature)
git checkout -b feat/m<N>-<short-name>   # first time only
git status

# 2. stage only the files belonging to this unit — never `git add .` blindly
git add <specific files>

# 3. commit with Conventional Commits
git commit -m "feat(payments): add ApprovePayment action with revenue-share freeze"
# prefixes: feat|fix|refactor|test|docs|chore  — scope in parentheses = module/domain

# 4. push immediately, every commit, not just at the end
git push -u origin feat/m<N>-<short-name>   # first push on the branch
git push                                     # subsequent pushes
```

When a milestone (or a safe, reviewable slice of it) is complete and checks pass (§9):
```bash
git push
gh pr create --base main --head feat/m<N>-<short-name> \
  --title "M<N>: <milestone name>" \
  --body  "Implements <what>. See docs/DECISIONS.md for choices made. Checks: pint/larastan/pest/lint/check/vitest all pass."
```
Wait for the owner to review and merge — do not merge your own PR unless told to. After merge:
```bash
git checkout main && git pull
```

Rules:
- Never go more than one logical unit of work without a commit, and never end a working session without pushing.
- Never commit secrets, `.env`, `.env.*` (except `.env.example`), `node_modules`, `vendor`, build output, or storage/log files — keep `.gitignore` accurate for both `/backend` and `/frontend` from Milestone 0.
- Never force-push a shared branch (`main`, any PR branch under review). Force-push only your own not-yet-reviewed feature branch, and only to fix your own last commit.
- Never rewrite or squash history that's already pushed and visible to the owner without saying so.
- If a commit breaks a check (§9), fix it in a follow-up commit immediately — don't leave `main`/the PR branch red.
- Keep commits small enough that `git log --oneline` on a branch reads like a changelog of the feature.

## 3. Repository Layout
```
/backend    Laravel API
/frontend   SvelteKit app (TypeScript, Svelte 5, Tailwind)
/docs       REQUIREMENTS.md, DECISIONS.md, API.md
AGENT.md
```
Keep `docs/API.md` updated with every endpoint (method, path, auth, request, response, errors).

**GitHub organization** (set up once, in Milestone 0):
- Create a GitHub Milestone for each item in REQUIREMENTS §18 (`M0 Foundation`, `M1 Identity`, ... `M8 Deployment`).
- Before starting a milestone's work, open one Issue per logical unit planned for it (from your posted plan in §2 step 1), assign it to that GitHub Milestone. Reference the issue number in commits/PRs (`feat(payments): ... (#12)`).
- Protect `main`: require PRs, require checks (§9) to pass, no direct pushes.
- Close each Issue via its merged PR (`Closes #12` in the PR description) so the GitHub Milestone progress bar reflects real status — this is the owner's at-a-glance view of where the project stands.

## 4. Backend Rules (Laravel)
- PHP 8.3+, `declare(strict_types=1);` in every PHP file. Follow PSR-12, format with Laravel Pint, analyse with Larastan (level 6 minimum, raise over time).
- **Structure by domain**: `app/Domain/{Identity,Catalog,Learning,Payments,Enrollment,Telegram,Settings}` (models, actions, policies, DTOs, events per domain). HTTP layer stays thin.
- Controllers ONLY: validate (Form Request) → authorize (Policy/permission) → call an Action/Service → return an API Resource. **No business logic in controllers or models.**
- One Action class per business operation (e.g. `ApprovePayment`, `CreateEnrollment`, `SubmitQuizAttempt`), single public `handle()`/`__invoke()`, unit-testable.
- Every endpoint MUST have authorization. No exceptions. Deny by default.
- **Money**: integer piasters only. Never float/decimal in PHP or JSON. Format only at the UI edge.
- Payment approval, enrollment creation, revenue-share freezing MUST run inside a DB transaction with row locking and be **idempotent**.
- Snapshot pricing and revenue shares on the payment record; never recompute historical payments from current prices.
- Migrations: reversible, with foreign keys, proper indexes, and explicit column types. NEVER edit a migration that has been merged; add a new one. Seeders for reference data (years, departments, initial courses, payment methods, roles/permissions, settings defaults).
- Prevent N+1 (eager load; enable `Model::preventLazyLoading()` outside production). Paginate all list endpoints.
- Use API Resources for every response, consistent error shape, HTTP status codes used correctly. Version under `/api/v1`.
- Queue everything slow or external (mail, Telegram API, image processing, scheduled expiry). Jobs must be idempotent with retries/backoff.
- Use `spatie/laravel-permission`, `spatie/laravel-activitylog`, `spatie/laravel-translatable` (or documented equivalents). Log all payment reviews, enrollment changes, price/discount/setting/role changes.
- Config via `.env` and `config/*.php` only. Never call `env()` outside config files.
- Translatable columns are JSON `{ar,en}`. Validation messages and mails localized.

## 5. Frontend Rules (SvelteKit)
- TypeScript strict. Svelte 5 (runes). Tailwind with design tokens from REQUIREMENTS §14. No inline hex colors in components; use tokens.
- SSR for public pages (landing, courses, course detail) for SEO and speed. Auth via Sanctum cookies; server `load` functions forward cookies to the API. NEVER store tokens in `localStorage`.
- One typed API client module; no scattered `fetch` calls. Handle loading, empty, and error states in every view.
- **i18n is mandatory**: zero hardcoded user-facing strings. Arabic is the default locale (root URLs), English under `/en`.
- **RTL correctness is mandatory**: use Tailwind logical utilities (`ms-*`, `me-*`, `ps-*`, `pe-*`, `text-start`, `start-*`, `end-*`), never `left/right` for layout. Mirror directional icons. Test every screen in both directions.
- Mobile-first responsive. Accessibility: semantic HTML, labelled inputs, focus states, AA contrast, keyboard operable.
- Forms validate on the client for UX and on the server for truth. Never trust the client.
- Keep components small and reusable; shared UI in `src/lib/components`. No giant files.

## 6. Security Rules (non-negotiable)
- Authorization on every resource; write tests proving a student cannot access another student's data, a teacher cannot access another teacher's course, an assistant admin cannot exceed permissions.
- Rate-limit: login, register, password reset, payment submission, Telegram webhook.
- Uploads: validate real MIME type + extension allowlist + size limit from settings, randomize file names, store in the **private** disk, serve only via short-lived signed URLs. Strip EXIF from images. Block executables/archives by default.
- Telegram webhook MUST verify the secret token header. Token/secret values only from env.
- Passwords hashed with Laravel defaults; enforce minimum strength; force password change for admin-created teachers.
- No secrets, tokens, or personal data in logs. HTTPS-only cookies in production, strict CORS to the frontend origin, security headers set.
- Prevent mass assignment (explicit `$fillable`/DTOs). Escape all output. Validate all input.
- Any security doubt: choose the safer option and note it in `docs/DECISIONS.md`.

## 7. Business Rules to Enforce in Code
- Roles: superadmin, admin (granular permissions), teacher (created by admin only), student.
- Teacher share default 70%, editable in settings, per-course/per-teacher overrides, frozen at payment approval.
- Access is per (student, course, term); expires at term end (+ grace days from settings).
- One pending payment per (user, course, term). Proof image mandatory. Duplicate proof hash is flagged, not auto-rejected.
- Discounts never stack; best single valid discount applies; price never below 0. Effective price 0 ⇒ instant free enrollment.
- One Telegram group per course; join requests approved only for users with active enrollment; expired/revoked users are removed.
- Email verification required before purchase.

## 8. Testing Requirements
- Backend: Pest feature tests for every endpoint and policy; unit tests for Actions (pricing/discount, approve payment, expiry, quiz grading, revenue share). Include negative/authorization tests.
- Frontend: component/unit tests where logic exists; Playwright for critical flows (register → checkout → pending; admin approves → student sees content).
- Tests MUST be deterministic, isolated, and run in CI. Use factories, not shared state.
- Bug fixes require a regression test.

## 9. Definition of Done (all must be true)
- Requirements for the milestone are implemented exactly, nothing extra.
- Migrations run fresh (`migrate:fresh --seed`) without errors.
- `pint`, `larastan`, `pest`, frontend `lint`, `check` (svelte-check), `vitest` all pass.
- No `TODO`/debug code/dead code left. No hardcoded strings or colors.
- Arabic + English and RTL + LTR verified for any UI touched.
- Docs updated: `docs/API.md`, `docs/DECISIONS.md`, README run instructions.
- Summary delivered to the owner (§2 step 5).

## 10. Communication Style
- Be concise and direct. Report facts, not enthusiasm.
- When you disagree with a requirement or see a risk, say so plainly with the reason and a recommended alternative, then follow the owner's decision.
- Never claim something works without having run it. If you could not run something, say so.
- Never invent library APIs; check installed versions and docs. If unsure, say you are unsure.

## 11. Forbidden
- Building beyond the current milestone or non-goals.
- Hardcoded secrets, credentials, or magic numbers for business rules that belong in settings.
- Business logic in controllers, models, Svelte components, or migrations.
- Floating-point money, unpaginated lists, N+1 queries, unauthorized endpoints.
- `left/right` based layout, hardcoded UI text, hardcoded colors.
- Silently changing architecture, dependencies, or requirements.
- Skipping tests or checks "to save time".