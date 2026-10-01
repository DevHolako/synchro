# Synchro Project Rules Index

This file maps file globs to rule files in `.agents/rules/`. Before planning, generating, or modifying code in this codebase, agents MUST consult every rule file whose glob covers the path(s) in scope. The repo-root `AGENTS.md` / `CLAUDE.md` remain the primary guidelines; these rules expand on them.

## Rule Mappings

| Glob Pattern                            | Rule File                                                                          | Category & Scope                                                                                        |
| :-------------------------------------- | :--------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------ |
| `**`                                    | [`localization.md`](./localization.md)                                             | **Full-Stack Localization (EN & FR)**: Tri-file parity, zero hardcoded user text, French default.       |
| `**`                                    | [`pre-plan-conversation-and-sparring.md`](./pre-plan-conversation-and-sparring.md) | **Pre-Plan Sparring**: Back-and-forth design dialogue before planning/coding features.                  |
| `**`                                    | [`mandatory-verification-tests.md`](./mandatory-verification-tests.md)             | **Verification Policy**: Targeted tests, full suites only on request.                                   |
| `**`                                    | [`antigravity-rtk-rules.md`](./antigravity-rtk-rules.md)                           | **RTK Command Proxy** (Antigravity only): Prefix shell commands with rtk to condense output.             |
| `**/*.php`                              | [`php.md`](./php.md)                                                               | **PHP 8.5 & Laravel Standards**: Promotion, typing, array shapes, Pint.                                 |
| `app/**/*.php`                          | [`no-raw-html-in-php.md`](./no-raw-html-in-php.md)                                 | **No Raw HTML in PHP**: Extract all HTML to Blade views in `resources/views/`.                          |
| `app/**`, `tests/**`                    | [`permissions-and-rbac.md`](./permissions-and-rbac.md)                             | **Strict Permission-Based Authorization**: Permissions are gate of check; roles are permission bundles. |
| `app/**`, `config/**`, `routes/**`, `tests/**` | [`queues-and-jobs.md`](./queues-and-jobs.md)                                | **Queues, Jobs & Deployment**: Redis + Horizon, thin jobs, named queues, Docker Compose (ADR 0012).     |
| `resources/js/**`                       | [`frontend.md`](./frontend.md)                                                     | **Frontend & Inertia v3 Standards**: React 19, Vercel React Best Practices, no monolithic JSX pages.    |
| `resources/js/**`                       | [`no-raw-fetch.md`](./no-raw-fetch.md)                                             | **No Raw fetch()**: Use Inertia v3 `useHttp` or visits with Wayfinder URLs.                             |
| `tests/**`                              | [`testing.md`](./testing.md)                                                       | **Pest & Test Enforcement**: Feature tests, model factories, assertions.                                |

---

## Instructions for Agents

1. **Scope Checking**: Identify all rule files that match the target path before writing or editing any code.
2. **Path Match Rule**: Read and strictly adhere to each matching rule's guidelines.
3. **Cross-Domain Verification**: For multi-layer changes (e.g. backend + frontend), review rules for both layers.

## Test Commands

- **Frontend changes** (`resources/js/**`): `npm run types:check` and `npx vp check resources/js`.
- **Backend changes**: `php artisan test --compact --filter=...` while iterating; `composer test` (Pint + PHPStan + Pest) for the full suite.
- **Front and back together**: `composer ci:check`.

## Commits

Do not include the `Co-Authored-By:` tag in commits.
