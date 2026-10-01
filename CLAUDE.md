# Synchro — Developer & Agent Guidelines

Synchro is the timetable scheduling and examination logistics platform for ISGA, catering to both executive/weekend programs ("Temps Aménagé") and standard daytime programs ("Formation Initiale").

---

## 🔒 1. Strict Permission-Based Authorization (MUST-FOLLOW BACKEND RULE)

- **Permissions are the Gate of Check**:
  - All authorization boundaries (Gates, Policies, Form Requests, Single-Action classes) MUST check specific atomic permissions defined in `App\Enums\Permission` (e.g. `Permission::CreateRooms`, `Permission::ManageReferentials`, `Permission::EnterGrades`).
  - Gates are registered for all `Permission::cases()` in `App\Providers\AppServiceProvider`.
  - Check capabilities via `$user->hasPermission(Permission::...)`, `$user->can('permission:name')`, or `Gate::authorize(...)`.
- **Roles are Strictly Permission Bundles**:
  - Roles (`Administrator`, `Coordinator`, `Teacher`, `Student` in `App\Enums\UserRole`) are ONLY groupings of permissions; they are never authorization gates themselves.
  - Each role defines its bundled permissions in `UserRole::permissions(): array`.
- **STRICT PROHIBITION**:
  - **NEVER** check `$user->role === ...`, `$user->isCoordinator()`, or `$user->isAdministrator()` in policies, gates, controllers, or actions.
  - **NEVER** write policy methods that compare roles directly. All policies (`RoomPolicy`, `CampusPolicy`, `BuildingPolicy`, etc.) must inspect permissions.

---

## ⚡ 2. Vercel React Best Practices & Component Decomposition (MUST-FOLLOW FRONTEND RULE)

- **Mandatory Skill**: Always activate and follow `/vercel-react-best-practices`.
- **Zero Monolithic JSX (Strict Prohibition of 500+ Line JSX Files)**:
  - Writing 500–1,000+ lines of JSX in a single file is STRICTLY REJECTED.
  - Page components in `resources/js/pages/` must remain lean orchestration views (ideally under 150–200 lines).
  - Every feature view MUST be decomposed into modular, single-responsibility components in `resources/js/pages/{feature}/components/`:
    - `types.ts`: Feature TypeScript interfaces and types.
    - `*-stats.tsx`: Summary metric cards.
    - `*-filter-bar.tsx`: Search inputs, selectors, and filter toggles.
    - `*-table.tsx` & `*-row.tsx`: Data tables and memoized rows.
    - `*-dialog.tsx`: Modals, create/edit forms, and action dialogs.
- **Performance Rules**:
  - Never define components inside components (`rerender-no-inline-components`).
  - Hoist static JSX and default props outside component render bodies.
  - Memoize list rows (`rerender-memo`) with primitive dependencies.
  - Avoid barrel files when importing heavy components.

---

## 🏛️ 3. Single-Action Architecture (ADR 0009)

- 100% of domain business logic and state mutations reside in dedicated Single-Action classes under `app/Actions/`.
- Web controllers (`app/Http/Controllers/Web/`) invoke Single-Action classes and return Inertia responses.
- API controllers (`app/Http/Controllers/Api/V1/`) invoke the exact same Single-Action classes and return JSON API resources.
- Controllers remain thin orchestrators of Form Requests, Actions, and responses.

---

## 📐 4. Dual Capacity Invariant

- Every room mandates `course_capacity` (integer > 0) and `exam_capacity` (integer > 0).
- `exam_capacity` strictly cannot exceed `course_capacity` (`exam_capacity <= course_capacity`).
- Room names must be unique per building.

---

## 🌐 5. Full-Stack Localization (EN Base, FR Default Display, Zero Hardcoded Strings)

- **Default Presentation is French (`fr`)**: The application user interface MUST display French (`fr`) by default.
- **English (`en`) Base Parity**: All translation dictionaries (`fr.ts` and `en.ts`) must maintain 100% complete key parity.
- **Zero Hardcoded Strings**: NEVER hardcode raw strings in React components, modals, tables, buttons, badges, tooltips, or toast notifications. All user-facing strings must use `const { t } = useTranslation()`.
- **Backend Messages**: All controller flash messages, redirects, and validation feedback must use `__('messages.xxx', [...])` via `lang/fr/messages.php` and `lang/en/messages.php`.
- **Tri-File Parity**: Every key must exist in `types.ts`, `fr.ts`, and `en.ts`.

---

## ⏱️ 6. Queues, Jobs & Deployment (ADR 0012)

- **Queue anything slow or external**: emails/notifications, spreadsheet imports, PDF generation, SMS/WhatsApp, and any third-party call MUST run as queued jobs (Redis + Horizon), never inline in a web request.
- **Jobs are thin**: classes in `app/Jobs/` only call Single-Action classes (`app/Actions/`); business logic never lives in a job.
- **Named queues**: `notifications` (mail/alerts), `imports` (long, single-attempt), `default`. Every queue must be served by a supervisor in `config/horizon.php`.
- **Timeout chain**: job `$timeout` < supervisor `timeout` < `REDIS_QUEUE_RETRY_AFTER` (`config/queue.php`).
- **After commit**: side effects that must not escape a rolled-back transaction (emails, external calls) are dispatched with `DB::afterCommit` or `->afterCommit()`.
- **Idempotency**: jobs must be safe to deliver twice (e.g. claim a `pending` record with a conditional update before processing).
- **Notifications** implement `ShouldQueue` and route channels to queues via `viaQueues()`.
- **Horizon dashboard** (`/horizon`) is gated by `Permission::MonitorQueues`, never by role.
- **Production** is the Docker Compose stack in `docker-compose.yml`: `app` (FrankenPHP, i.e. Caddy with PHP built in, bound to `127.0.0.1:${APP_PORT}`), `horizon`, `scheduler`, plus the optional `mysql` (8.0), `redis`, and `phpmyadmin` services selected with `COMPOSE_PROFILES`. The host's nginx (Hestia templates in `docker/hestia/`) owns the domain and HTTPS. Production env template: `.env.docker.example`. Locally, `composer dev` starts Horizon (Redis must be running).
- **Tests** run with `QUEUE_CONNECTION=sync`; assert dispatching with `Queue::fake()` and test job behaviour by calling `handle()` / the action directly.

---

## 🧪 7. Testing & Verification Workflow

Full policy: `.agents/rules/mandatory-verification-tests.md`.

- **Automatic, before finishing a change** (on the code you touched):
  - **Pest Tests**: targeted only, `php artisan test --compact --filter=TestName`.
  - **PHP Code Formatter**: `vendor/bin/pint --dirty --format agent`.
  - **Frontend Typecheck**: `npm run types:check`.
  - **Frontend Linter**: `npx vp check resources/js`.
- **Only when the user asks**: full suites (`composer test`, `composer ci:check`, unfiltered `php artisan test`) and the frontend build (`npm run build`).

---

## 📦 8. Git Commit Standards

- Follow Conventional Commits: `<type>(<scope>): <subject>` (`feat`, `fix`, `test`, `docs`, `refactor`, `chore`).
- Do not use `Co-Authored-By:` tags.
- Commit in clean, dependency-ordered layers (migrations/models $\rightarrow$ actions/controllers $\rightarrow$ UI $\rightarrow$ tests $\rightarrow$ docs).
