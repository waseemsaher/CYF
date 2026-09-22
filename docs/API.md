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

### GET /api/v1/reference/terms
- Purpose: returns public term metadata used by the catalog and enrollment workflow.
- Response: JSON object containing `data` and each term's `id`, `name`, `starts_at`, `ends_at`, `is_current`, and `sort_order`.
