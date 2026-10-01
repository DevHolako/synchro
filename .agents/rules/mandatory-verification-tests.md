# Test Verification Policy (On-Demand)

To conserve AI context window limits and usage quotas:

- **Do NOT automatically run the full suites (`composer test`, `composer ci:check`)** after every refactor, feature addition, bug fix, or code modification.
- **Only run verification tests when explicitly requested by the user.**
- When tests are requested, prefer running the narrowest targeted test (e.g. `php artisan test --compact --filter=TestName`) rather than full-suite runs unless the user asks for a complete suite run.
- **Frontend changes**: `npm run types:check` and `npx vp check resources/js`.
- **Backend or full-stack changes**: `composer test` (Pint + PHPStan + Pest), or `composer ci:check` for both layers.
