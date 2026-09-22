# API Contract

## Global conventions
- Base path: /api/v1
- Response format: JSON
- Auth: Laravel Sanctum cookie auth for SPA requests
- Errors: consistent JSON error envelopes
- Authorization: required for every endpoint

---

## Public endpoints

### GET /api/v1/courses
- Auth: public.
- Query parameters: optional `academic_year_id` and `department_id` audience filters.
- Response: paginated JSON with 12 courses per page. `data` contains published courses with `id`, `slug`, translatable `title` and `description`, `price_cents`, `list_price_cents`, `discount_cents`, `amount_due_cents`, `discount_id`, `status`, and `sort_order`. `meta` contains `current_page`, `last_page`, `per_page`, and `total`.

### GET /api/v1/courses/{slug}
- Auth: public.
- Response: one published course with the same course and calculated pricing fields as the listing endpoint. Returns `404` when the slug is missing or unpublished.

### GET /api/v1/reference/academic-years
- Auth: public.
- Response: JSON `data` array with each year's `id`, `name`, `sort_order`.

### GET /api/v1/reference/departments
- Auth: public.
- Response: JSON `data` array with each department's `id`, `code`, `name`, `sort_order`.

### GET /api/v1/reference/terms
- Auth: public.
- Response: JSON `data` array with each term's `id`, `name`, `starts_at`, `ends_at`, `is_current`, `sort_order`.

---

## Authentication

### POST /api/v1/register
- Auth: public.
- Body: `name`, `email`, `password`, `password_confirmation`, `branch`, `academic_year`, `department`.
- Response: user object with token.

### POST /api/v1/login
- Auth: public.
- Body: `email`, `password`.
- Response: user object with token.

---

## Student endpoints (auth required)

### GET /api/v1/me
- Response: current user profile.

### PUT /api/v1/profile
- Body: updatable profile fields.
- Response: updated user profile.

### POST /api/v1/payments
- Body: multipart form — `course_id`, `term_id`, `method`, `sender_identifier`, `proof` (image file), optional `student_note`.
- Response: 201 with PaymentResource (pending) or EnrollmentResource (free course instant enrollment).
- Errors: 422 if pending payment exists for same (user, course, term).

### GET /api/v1/payments
- Response: paginated list of student's own payments, newest first.

### GET /api/v1/payments/{id}
- Response: single payment detail. 403 if not the student's own.

### POST /api/v1/payments/{id}/cancel
- Cancels a pending payment. 403 if not owned or not pending.

---

## Admin endpoints (auth + permission required)

### Course management (requires `courses.manage` or `superadmin`)

### GET /api/v1/admin/courses
- Paginated list of draft, published, and archived courses.

### POST /api/v1/admin/courses
- Creates a course with translated title/description, integer price, status, optional `audiences` array.

### PUT /api/v1/admin/courses/{id}
- Partially updates a course. Supplying `audiences` replaces current audience rows.

### DELETE /api/v1/admin/courses/{id}
- Soft-deletes a course.

---

### Payment review (requires `payments.review` or `superadmin`)

### GET /api/v1/admin/payments
- Query: `status` (default: `pending`), `page`.
- Response: paginated payments with user and course relationships. Includes `counts` object with per-status totals.

### GET /api/v1/admin/payments/{id}
- Single payment with full details, signed proof URL, duplicate proof flag.

### POST /api/v1/admin/payments/{id}/approve
- Approves payment, creates enrollment, freezes revenue shares. Idempotent.
- Response: EnrollmentResource.

### POST /api/v1/admin/payments/{id}/reject
- Body: `rejection_reason` (required).
- Response: updated PaymentResource.

---

### Enrollment management (requires `admin` or `superadmin` role)

### POST /api/v1/admin/enrollments/grant
- Body: `user_id`, `course_id`, `term_id`.
- Creates enrollment with `source=admin_grant`. Response: 201 EnrollmentResource.

### POST /api/v1/admin/enrollments/{id}/revoke
- Sets enrollment status to `revoked`. Response: updated EnrollmentResource.

### PUT /api/v1/admin/enrollments/{id}/extend
- Body: `expires_at` (date, must be in the future).
- Response: updated EnrollmentResource.

---

## Telegram Integration

### POST /api/v1/telegram/webhook
- Auth: public, rate-limited (`throttle:60,1`).
- Headers: `X-Telegram-Bot-Api-Secret-Token` matching `config('telegram.webhook_secret')`.
- Handles `message` updates for `/start <token>` account linking.
- Handles `chat_join_request` updates for automatic enrollment verification and approval/decline.
- Response: `{"ok": true}` (or `403` if secret token header is missing/mismatched).

### GET /api/v1/telegram/status
- Auth: authenticated user (`auth:sanctum`).
- Response: `{"data": {"is_linked": bool, "telegram_user_id": int|null, "telegram_username": string|null}}`.

### POST /api/v1/telegram/link-token
- Auth: authenticated user (`auth:sanctum`).
- Response: `{"data": {"token": string, "deep_link": string, "expires_at": string}}`.
- Generates a 32-character single-use token expiring in 15 minutes. Deep link format: `https://t.me/<bot_username>?start=<token>`.

### POST /api/v1/telegram/unlink
- Auth: authenticated user (`auth:sanctum`).
- Unlinks the student's Telegram account (`telegram_user_id = null`, `telegram_username = null`).
- Response: `{"message": "Telegram account unlinked successfully."}`.

---

## Learning Content & Quizzes

### GET /api/v1/courses/{slug}/content
- Auth: optional.
- If not enrolled / unauthenticated: returns outline only with items flagged `is_locked: true` and sensitive URLs/files hidden.
- If actively enrolled / staff: returns full sections, items, lecture links, file download availability, and `telegram_invite_link`.

### GET /api/v1/courses/{slug}/items/{itemId}/file
- Auth: authenticated user with active enrollment (or staff).
- Streams / downloads private course file attachment. Returns 403 if not enrolled.

### POST /api/v1/quizzes/{quizId}/start
- Auth: authenticated enrolled student.
- Validates enrollment, time window (`available_from`/`available_until`), and `max_attempts`.
- Returns attempt object and questions list without leaking `is_correct` flags.

### POST /api/v1/quizzes/{quizId}/attempts/{attemptId}/submit
- Auth: authenticated attempt owner.
- Body: `answers` object mapping question IDs to selected option ID array.
- Auto-grades submission, calculates score, and marks attempt `submitted`.

### GET /api/v1/quizzes/{quizId}/attempts/{attemptId}
- Auth: attempt owner or staff.
- Returns score and question review breakdown according to `results_visibility` setting (`immediate`, `after_close`, or `hidden`).

### GET /api/v1/quizzes/{quizId}/my-attempts
- Auth: authenticated student.
- Returns list of past attempts and scores for the specified quiz.

---

## Admin Content Management

### Sections:
- `POST /api/v1/admin/courses/{course}/sections`: create course section.
- `PUT /api/v1/admin/sections/{section}`: update section title/position.
- `DELETE /api/v1/admin/sections/{section}`: delete section.

### Items:
- `POST /api/v1/admin/courses/{course}/sections/{section}/items`: create item (supports file uploads up to 50MB).
- `PUT /api/v1/admin/items/{item}`: update item.
- `DELETE /api/v1/admin/items/{item}`: delete item.

### Quizzes & Questions:
- `POST /api/v1/admin/courses/{course}/quizzes`: create quiz/exam.
- `POST /api/v1/admin/quizzes/{quiz}/questions`: create question with options.
- `DELETE /api/v1/admin/questions/{question}`: delete question.

---

## Teacher Portal (`auth:sanctum` + teacher role)

### GET /api/v1/teacher/dashboard
- Returns teacher's assigned courses, active student counts, earnings balance, total earned, total paid out, and payout history.

### GET /api/v1/teacher/courses/{courseId}/students
- Paginated student roster for teacher's course (read-only).

### GET /api/v1/teacher/quizzes/{quizId}/analytics
- Performance analytics: total attempts, average score, per-question correct percentage rate.

---

## Admin Overview & System Management (`auth:sanctum` + admin permissions)

### GET /api/v1/admin/overview
- Overview dashboard KPIs: pending payments, active enrollments, term revenue, total students, recent activity.

### GET /api/v1/admin/students
- Paginated student search (`q`) and filters (`branch`, `academic_year`, `department`).

### GET /api/v1/admin/teachers
- Teachers list with assigned courses and computed balances.

### POST /api/v1/admin/teachers
- Creates teacher account with temporary password (`must_change_password: true`) and assigns `teacher` role.

### POST /api/v1/admin/courses/{courseId}/teachers
- Assigns teacher to course with optional revenue share percent override.

### POST /api/v1/admin/teachers/{teacherId}/payouts
- Records manual payout to teacher and updates balance.

### GET /api/v1/admin/settings & PUT /api/v1/admin/settings
- View and update grouped platform configuration settings.

### GET /api/v1/content-blocks/{key} (Public)
- Retrieve translated content block or legal policy text.

### GET /api/v1/admin/content-blocks & PUT /api/v1/admin/content-blocks/{key}
- Admin view and update content blocks with cache invalidation.
