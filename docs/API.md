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

## Planned future endpoints
- Auth: register, login, logout, verify email, password reset
- Catalog: public course listing and detail endpoints
- Payments: submit proof, list payments, review queue
- Telegram: webhook and link-token handlers
- Learning: course content, quiz endpoints
- Admin: config and dashboard endpoints
