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
