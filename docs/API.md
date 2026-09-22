# API Contract (Foundation Stub)

This file is intentionally a foundation stub for the project. The first production API contract will be expanded as milestones are implemented.

## Global conventions
- Base path: /api/v1
- Response format: JSON
- Auth: Laravel Sanctum cookie auth for SPA requests
- Errors: consistent JSON error envelopes
- Authorization: required for every endpoint

## Current state
- No business endpoints are implemented yet.
- This milestone only sets up the repository, tooling, and scaffolding.
- Catalog public endpoints are available for published courses and reference metadata.
- Current public reference data includes academic years, departments, and terms.

## Planned future endpoints
- Auth: register, login, logout, verify email, password reset
- Catalog: public course listing and detail endpoints
- Payments: submit proof, list payments, review queue
- Telegram: webhook and link-token handlers
- Learning: course content, quiz endpoints
- Admin: config and dashboard endpoints

## Public catalog endpoints

### GET /api/v1/courses
- Auth: public.
- Query parameters: optional `academic_year_id` and `department_id` audience filters.
- Response: paginated JSON with 12 courses per page. `data` contains published courses with `id`, `slug`, translatable `title` and `description`, `price_cents`, `list_price_cents`, `discount_cents`, `amount_due_cents`, `discount_id`, `status`, and `sort_order`. `meta` contains `current_page`, `last_page`, `per_page`, and `total`.

### GET /api/v1/courses/{slug}
- Auth: public.
- Response: one published course with the same course and calculated pricing fields as the listing endpoint. Returns `404` when the slug is missing or unpublished.

### Admin course management
- `GET /api/v1/admin/courses`: authenticated users with `courses.manage` or `superadmin`; paginated list of draft, published, and archived courses.
- `POST /api/v1/admin/courses`: same authorization; creates a course with translated title/description, integer price, status, optional catalog settings, and an `audiences` array of `{academic_year_id, department_id}` pairs.
- `PUT /api/v1/admin/courses/{id}`: same authorization; partially updates a course. Supplying `audiences` replaces the course's current audience rows; omitting it leaves them unchanged.
- `DELETE /api/v1/admin/courses/{id}`: same authorization; soft-deletes a course.

### GET /api/v1/reference/terms
- Purpose: returns public term metadata used by the catalog and enrollment workflow.
- Response: JSON object containing `data` and each term's `id`, `name`, `starts_at`, `ends_at`, `is_current`, and `sort_order`.
