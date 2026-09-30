---
title: Pest & Automated Testing Guidelines
globs: 'tests/**'
---

# Pest & Automated Testing Guidelines

Testing in We Creatif is powered by Pest PHP for backend tests and Jest/TypeScript/ESLint for frontend tests.

## 1. Test Architecture & Pest

- Use Pest for all PHP tests. Create new tests via `php artisan make:test --pest {Name}`.
- Do not prefix directory names in `{Name}` (e.g. use `QualityAuditTest`, not `Feature/QualityAuditTest`).
- Default to Feature tests (`tests/Feature/`) to test realistic HTTP and domain integration flows.
- Never delete existing test files or assertions without explicit approval.

## 2. Test Isolation & Factories

- Always use Eloquent Model Factories (`User::factory()`, `Project::factory()`, etc.) instead of manual database seeding in tests.
- Leverage custom factory states where available (`->admin()`, `->locked()`, `->withBrief()`).
- Use `fake()` for randomized mock data instead of hardcoded strings.

## 3. Running & Verifying Tests

- **Frontend-only changes** (`resources/js/**`): Run `npm run test` — runs Prettier check, ESLint, and TypeScript type-check.
- **Backend-only changes** (`app/**`, `routes/**`, `database/**`, `tests/**`): Run `composer run test` — runs Pint lint, PHPStan types, and Pest with `--tia` (test-it-all).
- **Full-stack changes** (both frontend and backend): Run `composer run full-test` (it covers both layers).
- **Targeted iteration**: Run `vendor/bin/pest tests/Feature/SpecificTest.php` or `vendor/bin/pest --filter=test_name` to iterate on a single test before running the full suite.
- Ensure all tests pass without regressions before marking changes complete.
