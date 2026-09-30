---
title: Frontend React, Inertia v3 & TypeScript Standards
globs: "resources/js/**"
---

# Frontend React, Inertia v3 & TypeScript Standards

The frontend is an Inertia.js v3 SPA built with React 19, TypeScript, and Tailwind CSS.

## 1. Inertia v3 Architecture
- Components and views live under `resources/js/pages/`.
- Use Inertia features natively: layout props (`setLayoutProps`), standalone requests (`useHttp`), instant visits, prefetching, and optimistic updates.
- In Inertia v3, Axios is removed in favor of the built-in XHR client.
- When working with deferred props, always include an empty state with a pulsating or animated skeleton.

## 2. Route Generation & Navigation (Laravel Wayfinder)
- NEVER hardcode URL paths for backend endpoints in frontend links or forms.
- Always use auto-generated Wayfinder typed actions or named routes:
  - Controllers: import from `@/actions/...`
  - Named routes: import from `@/routes/...`
- Use Wayfinder's `.url()`, `.get()`, or `.post()` methods for type-safe route binding.

## 3. TypeScript & Lint Hygiene
- Strict zero-warning policy: code must pass `npm run test` (`format:check`, `lint:check`, `types:check`).
- **Unused Variables**: Do not leave unused variables or destructured parameters (e.g. `locale` from `useTranslation()`).
- **Import Ordering**: Enforce `import-x/order` (built-in packages $\rightarrow$ external modules $\rightarrow$ internal aliases `@/...` $\rightarrow$ relative imports).
- **Code Style**: Blank lines are required before control flow statements (`if`, `return`, `switch`).
- **Format**: Run `npx prettier --write <file>` if any format check fails.

## 4. Localization Synchronization
- UI text must be consumed via `const { t } = useTranslation()`.
- Any new key must be added simultaneously across `resources/js/i18n/types.ts`, `en.ts`, and `fr.ts`.

## 5. Mandatory Vercel React Best Practices & Component Decomposition (STRICT)
- **Activate & Follow `/vercel-react-best-practices`**: All frontend React components and pages MUST strictly adhere to Vercel React Best Practices.
- **Zero Monolithic JSX (Strict Prohibition of 500+ Line JSX Files)**:
  - Writing 500–1000+ lines of JSX in a single file is strictly forbidden.
  - Page components under `resources/js/pages/` must remain lean orchestration views (ideally under 150–200 lines).
  - Decompose every feature view into modular, single-responsibility components placed in `resources/js/pages/{feature}/components/`:
    - `types.ts` for shared interfaces and types
    - `*-stats.tsx` for metric cards
    - `*-filter-bar.tsx` for search and filtering controls
    - `*-table.tsx` and `*-row.tsx` for data listing and row actions
    - `*-dialog.tsx` for create/edit modals and forms
- **Re-render & Bundle Optimization**:
  - Never define components inside other components (`rerender-no-inline-components`).
  - Extract list rows into memoized components (`RoomRow`, `SessionRow`) with primitive dependencies.
  - Hoist static JSX and default props outside component render bodies.
  - Avoid barrel files when importing heavy components (`bundle-barrel-imports`).
