# Test Verification Policy

To conserve AI context window limits and usage quotas, verification is split into cheap targeted checks (run automatically) and full suites (run only on request).

## Automatic: before declaring a change done

Run these on the code you touched, without being asked:

- **Backend**: the narrowest targeted tests for the changed behaviour (`php artisan test --compact --filter=TestName`) and `vendor/bin/pint --dirty --format agent`.
- **Frontend** (`resources/js/**`): `npm run types:check` and `npx vp check resources/js`.

## On request only

- **Do NOT automatically run the full suites** (`composer test`, `composer ci:check`, an unfiltered `php artisan test`) or `npm run build` after a refactor, feature, or fix.
- Run them only when the user explicitly asks:
    - **Backend or full-stack**: `composer test` (Pint + PHPStan + Pest), or `composer ci:check` for both layers.
    - **Production assets**: `npm run build`.
