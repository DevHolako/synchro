---
title: Pest & Automated Testing Guidelines
globs: 'tests/**'
# `paths` scopes this rule for Claude Code; `globs` for Antigravity.
paths:
  - "tests/**"
---

# Pest & Automated Testing Guidelines

Testing in Synchro uses Pest for backend tests; the frontend is verified with TypeScript and `vp check` (format + lint).

## 1. Test Architecture & Pest

- Use Pest for all PHP tests. Create new tests via `php artisan make:test --pest {Name}`.
- Do not prefix directory names in `{Name}` (e.g. use `ImportQueueTest`, not `Feature/ImportQueueTest`).
- Default to Feature tests (`tests/Feature/`) to test realistic HTTP and domain integration flows.
- Never delete existing test files or assertions without explicit approval.

## 2. Test Isolation & Factories

- Always use Eloquent Model Factories (`User::factory()`, `Room::factory()`, etc.) instead of manual database seeding in tests.
- Leverage custom factory states where available (`->admin()`, `->coordinator()`, `->invited()`).
- Use `fake()` for randomized mock data instead of hardcoded strings.

## 3. Running & Verifying Tests

- **Frontend changes** (`resources/js/**`): `npm run types:check` and `npx vp check resources/js`.
- **Backend changes** (`app/**`, `routes/**`, `database/**`, `tests/**`): `composer test` — Pint check, PHPStan, and the full Pest suite.
- **Full-stack changes**: `composer ci:check` covers both layers.
- **Targeted iteration**: `php artisan test --compact --filter=TestName` (or a single file path) before running the full suite.
- Ensure all tests pass without regressions before marking changes complete.
