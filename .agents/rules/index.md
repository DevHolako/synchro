# We Creatif Project Rules Index

This file maps file globs to rule files in `.agents/rules/`. Before planning, generating, or modifying code in this codebase, agents MUST consult every rule file whose glob covers the path(s) in scope.

## Rule Mappings

| Glob Pattern      | Rule File                                                                          | Category & Scope                                                                                           |
| :---------------- | :--------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------------------------------- |
| `**`              | [`localization.md`](./localization.md)                                             | **Full-Stack Localization (EN & FR)**: Bilingual parity, zero hardcoded user text.                         |
| `**`              | [`pre-plan-conversation-and-sparring.md`](./pre-plan-conversation-and-sparring.md) | **Pre-Plan Sparring**: Back-and-forth design dialogue before planning/coding features.                     |
| `**`              | [`mandatory-verification-tests.md`](./mandatory-verification-tests.md)             | **Verification Policy**: Test execution guidelines, targeted test preferences.                             |
| `**`              | [`antigravity-rtk-rules.md`](./antigravity-rtk-rules.md)                           | **RTK Command Proxy**: Prefix shell commands with rtk to condense output.                                  |
| `**`              | [`headless-browser-checks.md`](./headless-browser-checks.md)                       | **Headless Browser Checks**: Check a change in a headless browser (Playwright) only after asking the user. |
| `**/*.php`        | [`php.md`](./php.md)                                                               | **PHP 8.5 & Laravel Standards**: Promotion, strict types, array shapes, Pint.                              |
| `app/**/*.php`    | [`no-raw-html-in-php.md`](./no-raw-html-in-php.md)                                 | **No Raw HTML in PHP**: Extract all HTML to Blade views in `resources/views/`.                             |
| `app/**/*.php`    | [`no-raw-prompts-in-php.md`](./no-raw-prompts-in-php.md)                           | **No Raw Prompts in PHP**: Instructions, contracts and user messages each have one home (ADR 0005).        |
| `app/**`          | [`architecture.md`](./architecture.md)                                             | **DDD & CPS Pipeline Architecture**: Action pattern, domain boundaries, audit logs.                        |
| `app/**`          | [`permissions-and-rbac.md`](./permissions-and-rbac.md)                             | **Strict Permission-Based Authorization**: Permissions are gate of check; roles are permission bundles.    |
| `resources/js/**` | [`frontend.md`](./frontend.md)                                                     | **Frontend & Inertia v3 Standards**: React 19, Vercel React Best Practices, no monolithic JSX pages.      |
| `resources/js/**` | [`no-raw-fetch.md`](./no-raw-fetch.md)                                             | **No Raw fetch()**: Prohibit raw fetch/axios; use Inertia v3 useHttp or @/lib/http with Wayfinder.         |
| `resources/js/**` | [`frontend-page-layout-and-enums.md`](./frontend-page-layout-and-enums.md)         | **Layout & Enums**: Full-width views (no `mx-auto`), enum badges, async locks.                             |
| `resources/js/**` | [`ui-theme-and-color-harmony.md`](./ui-theme-and-color-harmony.md)                 | **Theme & Colors**: Primary vs secondary hierarchy, status colors vs theme tokens.                         |
| `tests/**`        | [`testing.md`](./testing.md)                                                       | **Pest & Test Enforcement**: Feature tests, model factories, assertions.                                   |

---

## Instructions for Agents

1. **Scope Checking**: Identify all rule files that match the target path before writing or editing any code.
2. **Path Match Rule**: Read and strictly adhere to each matching rule's guidelines.
3. **Cross-Domain Verification**: For multi-layer changes (e.g. backend + frontend), review rules for both layers.

## Test Commands

- **Front and back at the same time** : Run `composer run full-test` (Prettier + ESLint + TypeScript + Pint + PHPStan + Pest --tia).

- **Frontend-only changes** (`resources/js/**`): Run `npm run test` (Prettier + ESLint + TypeScript).

- **Backend or full-stack changes**: Run `composer run test` (Pint + PHPStan + Pest --tia).

# dont include the Co-Authored-By: tag in the commites
