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
