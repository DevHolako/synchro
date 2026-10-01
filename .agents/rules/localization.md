---
title: Full-Stack Localization & Multi-Language Standards
globs: '**'
---

# Full-Stack Localization & Multi-Language Standards

Synchro is a bilingual platform supporting both English (`en`) and French (`fr`), with French (`fr`) configured as the default UI presentation. All agents working in this repository must strictly prioritize and enforce localization standards when generating, modifying, or reviewing code.

## 1. Never Hardcode User-Facing Text

- **Backend PHP**: Never hardcode user-facing strings in controllers, middleware, requests, actions, jobs, or services.
- **Frontend React**: Never hardcode raw user-facing strings in React components, modals, buttons, table headers, tooltips, or toast notifications.

## 2. Backend Translation Standards

- **File Organization**: Translation strings live in `lang/en/` and `lang/fr/`. `messages.php` holds flash messages, controller feedback, redirects, validation and import errors, and access-control errors. Add a new domain file only when a feature outgrows it, and create it in both languages.
- **Helper Usage**: Always use Laravel's `__('filename.key', [...])` helper with named replacement tokens. Never use string concatenation for translated messages.
- **Database Consistency**: Persist database attributes, status codes, and enum values in clean English/code identifiers. Translate them at the presentation layer.

## 3. Frontend Translation Standards (`resources/js/i18n/`)

- **Tri-File Parity**: Every new or updated translation key MUST be defined across all three i18n files:
    1. `resources/js/i18n/types.ts` (TypeScript interface contract).
    2. `resources/js/i18n/en.ts` (English dictionary).
    3. `resources/js/i18n/fr.ts` (French dictionary).
- **Component Consumption**:
    - Always use `const { t } = useTranslation();` from `@/i18n/LanguageContext`.
    - Do NOT destructure unused values (such as `locale`) unless they are actively used in JSX or logic, to prevent unused-variable lint errors.
    - Pass parameterized tokens via objects: `t('namespace.key', { count: 5 })`.
    - Never add a fallback after `t()` (`t('key') || 'Text'`, `t('key') ?? ''`). The `types.ts` contract makes every key exist in both dictionaries, so fallbacks are dead code that hide hardcoded strings.
    - No hardcoded text in JSX children, user-facing attributes (`title`, `placeholder`, `alt`, `label`, `description`, `aria-*`), or `toast()` messages.
    - No locale ternaries (`locale === 'fr' ? 'Bonjour' : 'Hello'`).

## 4. Verification Workflow

Whenever translations, components, or backend classes are modified:

- **Frontend** (`resources/js/**`): `npm run types:check` (TypeScript) and `npx vp check resources/js` (format + lint).
- **Backend**: targeted Pest tests (`php artisan test --compact --filter=...`); `composer test` runs Pint, PHPStan, and the full suite.
