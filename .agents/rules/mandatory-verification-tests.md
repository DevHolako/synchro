# Test Verification Policy (On-Demand)

To conserve AI context window limits and usage quotas:

- **Do NOT automatically run `npm run test` or `composer run test`** after every refactor, feature addition, bug fix, or code modification.
- **Only run verification tests when explicitly requested by the user.**
- When tests are requested, prefer running the narrowest targeted test (e.g. `vendor/bin/pest tests/Feature/SpecificTest.php` or `vendor/bin/pest --filter=test_name`) rather than full-suite runs unless the user asks for a complete suite run.
- **Frontend-only changes**: `npm run test` (Prettier + ESLint + TypeScript).
- **Backend or full-stack changes**: `composer run test` (Pint + PHPStan + Pest --tia).
