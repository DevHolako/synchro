---
title: Full-Stack Localization & Multi-Language Standards
globs: '**'
---

# Full-Stack Localization & Multi-Language Standards

We Creatif is a bilingual platform supporting both English (`en`) and French (`fr`). All agents working in this repository must strictly prioritize and enforce localization standards when generating, modifying, or reviewing code.

## 1. Never Hardcode User-Facing Text

- **Backend PHP**: Never hardcode user-facing strings in controllers, middleware, requests, actions, services, schemas, or prompt templates.
- **Frontend React**: Never hardcode raw user-facing strings in React components, modals, buttons, table headers, tooltips, or toast notifications.

## 2. Backend Translation Standards

- **File Organization**: Group translation strings in `lang/en/` and `lang/fr/` under domain files:
    - `messages.php`: Flash messages, controller feedback, redirects, and access-control errors.
    - `audit.php`: Activity and audit log event descriptions, field diff labels, and actor descriptions.
    - `quality.php`: Audit rule descriptions, warnings, remediation steps, and report titles.
    - `schemas.php`: Industry brief templates, sitemap presets, section names, and field placeholders.
    - `ai.php`: System prompts, step instructions, and agent prompt templates.
- **Helper Usage**: Always use Laravel's `__('filename.key', [...])` helper with named replacement tokens. Never use string concatenation for translated messages.
- **Database Consistency**: Persist database attributes, status codes, and enum values in clean English/code identifiers. Translate dynamically at the presentation layer or event resolution layer (e.g. `ActivityLog::resolveEventDescription()`).

## 3. Frontend Translation Standards (`resources/js/i18n/`)

- **Tri-File Parity**: Every new or updated translation key MUST be defined across all three i18n files:
    1. `resources/js/i18n/types.ts` (TypeScript interface contract).
    2. `resources/js/i18n/en.ts` (English dictionary).
    3. `resources/js/i18n/fr.ts` (French dictionary).
- **Component Consumption**:
    - Always use `const { t } = useTranslation();` from `@/i18n/LanguageContext`.
    - Do NOT destructure unused values (such as `locale`) unless they are actively used in JSX or logic, to prevent `@typescript-eslint/no-unused-vars` lint errors.
    - Pass parameterized tokens via objects: `t('namespace.key', { count: 5 })`.
    - Never add a fallback after `t()` (`t('key') || 'Text'`, `t('key') ?? ''`). Keys are validated to exist in both dictionaries, so fallbacks are dead code that hide hardcoded strings.
- **ESLint Enforcement (`local/translations`)**: A single rule in `eslint/rules/translations.js` enforces:
    - Every static `t()` key exists in both `en.ts` and `fr.ts`.
    - No `||` / `??` fallback after `t()` (autofixable with `eslint --fix`).
    - No hardcoded text in JSX children, user-facing attributes (`title`, `placeholder`, `alt`, `label`, `description`, `aria-*`), or `toast()` messages. Content inside `<style>`, `<script>`, `<code>`, `<pre>`, `<kbd>` is exempt, as is text without letters.
    - No locale ternaries (`locale === 'fr' ? 'Bonjour' : 'Hello'`).

## 4. Verification Workflow

Whenever translations, components, or backend classes are modified:

- **Frontend-only** (`resources/js/**`): Run `npm run test` (Prettier + ESLint + TypeScript).
- **Backend or full-stack**: Run `composer run test` (Pint + PHPStan + Pest --tia).
