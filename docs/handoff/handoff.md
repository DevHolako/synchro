# Synchro — Architectural Handoff & Implementation Frontier

## 1. Executive Summary & Repository Identity

- **Project:** Synchro — Higher Education Timetable Scheduling & Examination Logistics Platform (ISGA).
- **Remote Repository:** `https://github.com/DevHolako/synchro.git` (`origin/main`).
- **Framework & Core Stack:**
  - **Backend:** Laravel 13.34.0, PHP 8.5, Laravel Fortify (public self-registration disabled), Laravel Wayfinder.
  - **Frontend:** Inertia.js (React 19 SPA), Tailwind CSS v4, Lucide React icons.
  - **Queues & Infrastructure:** Redis queues supervised by Laravel Horizon; production ships as a Docker Compose stack (FrankenPHP/Caddy app, Horizon, scheduler, MySQL 8.0, Redis) behind the host's nginx — see ADR 0012.
  - **Testing & Tooling:** Pest 5.2.1, Vite Plus (`vp`), Laravel Pint.
- **Current Test Status:** 140 tests registered (135 passed, 5 skipped: 3 Fortify 2FA stubs + 2 legacy registration tests skipped because registration is disabled), 660 assertions, 100% green.
- **Code Quality & Linting:**
  - TypeScript: `npx tsc --noEmit` clean (0 errors).
  - Frontend Lint: `npx vp check resources/js` passing (104 files clean, 0 errors, 0 warnings).
  - Code Style: Laravel Pint formatted (`vendor/bin/pint --dirty --format agent`).
  - Production Asset Compilation: `npm run build` succeeds cleanly.

---

## 2. Settled Domain Architecture & Mandatory Rules

Any agent or developer working on this codebase **MUST** follow these architectural invariants:

### A. Strict Permission-Based Authorization (ADR 0011)
- **Permissions are the Gate of Check:** Every authorization gate, policy, form request, and middleware MUST inspect specific atomic permissions defined in [`App\Enums\Permission`](file:///home/holako/github/synchro/app/Enums/Permission.php) (e.g., `Permission::CreateRooms`, `Permission::ManageReferentials`, `Permission::ManageUsers`).
- **Roles are Strictly Permission Bundles:** Roles (`Administrator`, `Coordinator`, `Teacher`, `Student` in [`App\Enums\UserRole`](file:///home/holako/github/synchro/app/Enums/UserRole.php)) are ONLY groupings of permissions. They must **NEVER** be evaluated directly as authorization gates.
- **Strict Prohibition:** NEVER check `$user->role === ...`, `$user->isCoordinator()`, or `$user->isAdministrator()` in policies, gates, controllers, or actions. Always check `$user->hasPermission(Permission::...)` or `Gate::authorize(...)`.
- **Dynamic Gates:** Gates are registered for all `Permission::cases()` in `AppServiceProvider`.

### B. Dual-Engine Single-Action Architecture (ADR 0009)
- 100% of domain business logic and state transitions reside in dedicated Single-Action classes under `app/Actions/{Domain}/`.
- Controllers contain **zero** business logic:
  - Web controllers (`app/Http/Controllers/Web/`) invoke actions and return Inertia React views.
  - API controllers (`app/Http/Controllers/Api/V1/`) invoke identical actions and return Sanctum-authenticated JSON API resources.

### C. Vercel React Best Practices & Zero Monolithic JSX
- **Mandatory Skill:** Always activate and follow `vercel-react-best-practices`.
- **Strict Prohibition:** Pages exceeding 150–200 lines of JSX are strictly rejected.
- **Mandatory Component Decomposition:** Every page in `resources/js/pages/{feature}/` must be an orchestration view broken into dedicated modular subcomponents in `resources/js/pages/{feature}/components/`:
  - `types.ts` for feature interfaces, props, and view models.
  - `*-stats.tsx` for summary metric cards.
  - `*-filter-bar.tsx` for search inputs, filter dropdowns, and create triggers.
  - `*-table.tsx` and `*-row.tsx` for listings and tables (memoized with `React.memo`).
  - `*-dialog.tsx` for creation, edit, and deletion modals.
- **Performance Rules:** Never define components inside other components; hoist static JSX and default non-primitive objects outside component bodies.

### D. Full-Stack Localization (FR Default Display, EN Base, Zero Hardcoded Strings)
- **French (`fr`) Default Presentation:** The application UI displays French by default. Users may toggle between French and English via `LanguageSwitcher`.
- **100% Key Parity:** Every translation key MUST be defined across all three client files and backend files:
  1. `resources/js/i18n/types.ts` (TypeScript interface contract).
  2. `resources/js/i18n/fr.ts` (French dictionary - default presentation).
  3. `resources/js/i18n/en.ts` (English dictionary - base).
  4. Backend: `lang/fr/messages.php` and `lang/en/messages.php`.
- **Zero Hardcoded Strings:** NEVER hardcode user-facing strings in React components, modals, buttons, table headers, badges, tooltips, or toast notifications. Every piece of user-facing text MUST be retrieved via `const { t } = useTranslation()`. Flash messages from controllers must use `__('messages.xxx', [...])`.

---

## 3. Specifications & Completed Frontier Status

All specifications and vertical slice tickets are tracked in [`docs/specs/README.md`](file:///home/holako/github/synchro/docs/specs/README.md).

### Completed Tickets in Part 01 (Core Foundation & Referentials)
1. **Ticket 01: Campuses, Buildings, Rooms** ([`01-campuses-buildings-rooms.md`](file:///home/holako/github/synchro/docs/specs/01-core-foundation-and-referentials/tickets/01-campuses-buildings-rooms.md))
   - Implemented: `Campus`, `Building`, `Room`, `RoomType`.
   - Business Rule: `exam_capacity <= course_capacity` enforced in actions and form requests.
   - Actions: `CreateRoomAction`, `UpdateRoomAction`, `ToggleRoomActiveAction`.
   - UI: `resources/js/pages/rooms/` fully modularized and translated.
2. **Ticket 02: Academic Hierarchy — Departments, Programs, Student Groups** ([`02-academic-structure-departments-programs-groups.md`](file:///home/holako/github/synchro/docs/specs/01-core-foundation-and-referentials/tickets/02-academic-structure-departments-programs-groups.md))
   - Implemented: `Department`, `Program`, `StudentGroup`, `Modality` enum (`formation_initiale`, `temps_amenage`).
   - Business Rules: Program modality cascading, group headcount validation against room capacities.
   - Actions: `CreateDepartmentAction`, `UpdateDepartmentAction`, `CreateProgramAction`, `UpdateProgramAction`, `CreateStudentGroupAction`, `UpdateStudentGroupAction`.
   - UI: `resources/js/pages/departments/`, `programs/`, `student-groups/` fully modularized and translated.
3. **Ticket 03: Course Modules & Syllabus Catalog** ([`03-module-syllabus-catalog.md`](file:///home/holako/github/synchro/docs/specs/01-core-foundation-and-referentials/tickets/03-module-syllabus-catalog.md))
   - Implemented: `CourseModule`, `ModuleStatus` enum (`draft`, `active`, `archived`).
   - Business Rule: `lecture_hours + tp_hours <= total_hours` and teacher assignment validation.
   - Actions: `CreateModuleAction`, `UpdateModuleAction`, `ToggleModuleActiveAction`.
   - UI: `resources/js/pages/modules/` fully modularized and translated with hex color badge indicators.
4. **Ticket 04: User Roles and Expiring Invitation Token Provisioning** ([`04-rbac-and-invitation-token-provisioning.md`](file:///home/holako/github/synchro/docs/specs/01-core-foundation-and-referentials/tickets/04-rbac-and-invitation-token-provisioning.md))
   - Implemented: `TeacherProfile`, `StudentProfile`, `InvitationToken` (SHA-256 hash stored, plain token only in the signed URL), `AccountStatus` enum (`invited`, `active`) with `users.status` / `users.activated_at`.
   - Permissions: added `ViewUsers` (`view:users`) and `ProvisionUsers` (`provision:users`) next to `ManageUsers`; `UserPolicy` gates on them. Only the Administrator bundle holds them today.
   - Business Rules: public Fortify registration disabled; only `active` accounts can log in (`Fortify::authenticateUsing`); tokens are single-use, valid 72 hours (`InvitationToken::LIFETIME_HOURS`), and revoked on resend/activation/temporary password.
   - Actions: `ProvisionUserAction`, `IssueInvitationAction`, `ResendInvitationAction`, `FindPendingInvitationAction`, `ActivateUserInvitationAction`, `IssueTemporaryPasswordAction`.
   - Role profiles: `UserRole::profileRelation()` maps a role to its profile relation, so actions never branch on role values.
   - UI: `resources/js/pages/users/` (directory, invite dialog, resend, one-time temporary password dialog), `auth/accept-invitation.tsx`, `auth/invitation-invalid.tsx`. `auth.permissions` is now a shared Inertia prop (used to show the sidebar "Users" link).

5. **Ticket 05: Bulk Spreadsheet Importer for Referentials and Users** ([`05-bulk-csv-excel-importer.md`](file:///home/holako/github/synchro/docs/specs/01-core-foundation-and-referentials/tickets/05-bulk-csv-excel-importer.md))
   - Implemented: `ImportType` enum (`rooms`, `modules`, `teachers`, `students`) defining columns, required columns, template example rows, the policy ability, and the row importer; `SpreadsheetReader` (first sheet of `.csv`/`.xlsx` via `openspout/openspout`, auto-detects `,`/`;`/tab and Windows-1252, keeps real row numbers, max 2000 rows).
   - Actions: `ImportReferentialsAction` validates every row first (rules, in-file duplicates, reference resolution) and only then writes all rows in one transaction; any failure rolls everything back. Row importers (`app/Actions/Imports/Importers/`) delegate to `CreateRoomAction`, `CreateModuleAction`, and `ProvisionUserAction`.
   - Queued processing: `QueueSpreadsheetImportAction` stores the upload and creates a `SpreadsheetImport` record (`pending`), then `ProcessSpreadsheetImportJob` (queue `imports`, 1 try, 600s timeout) calls `RunSpreadsheetImportAction`, which claims the record (`pending` → `processing`, so redelivery is a no-op), runs `ImportReferentialsAction`, stores the outcome and row errors, and deletes the file. `failed()` records crashes/timeouts.
   - Invitations: `ProvisionUserAction` issues invitations via `DB::afterCommit`, and `UserInvitationNotification` is queued on `notifications`, so a rolled-back import sends no emails.
   - Permissions: new `ImportReferentials` (`import:referentials`) held by Administrator and Coordinator; each type additionally requires its create ability (coordinators can import rooms/modules, not accounts).
   - UI: `resources/js/pages/imports/` wizard (type picker, column guide with CSV template download, drag-and-drop zone) plus a recent-imports history that polls (`usePoll`) while an import is queued/processing and shows row-level errors.

### Cross-cutting: Queues, Horizon & Docker Compose (ADR 0012)
- **Queues:** `notifications` + `default` (supervisor `supervisor-default`, 3 tries with backoff, 60s) and `imports` (supervisor `supervisor-imports`, 1 try, 630s). `REDIS_QUEUE_RETRY_AFTER` = 700. Horizon dashboard at `/horizon`, gated by `Permission::MonitorQueues` (Administrator).
- **Scheduler** (`routes/console.php`): `horizon:snapshot` every 5 minutes, `imports:fail-stale` every 15 minutes (fails imports pending for 6 hours or processing 20 minutes past the job timeout), `queue:prune-failed --hours=168` and `model:prune` daily (finished `SpreadsheetImport` after 90 days, unusable `InvitationToken` after 30 days except each user's latest).
- **Docker** (modelled on the we-cretif setup): `Dockerfile` (FrankenPHP `dunglas/frankenphp:1-php8.5`, composer and node builder stages, target `production`), `docker-compose.yml`, `docker/Caddyfile`, `docker/entrypoint.sh`, `docker/hestia/synchro.{tpl,stpl}` (host nginx templates), `.env.docker.example`. `app` serves HTTP on `127.0.0.1:${APP_PORT:-8000}` (Caddy `auto_https off`) and migrates on boot; `app`, `horizon`, and `scheduler` share the `synchro:latest` image and the storage volume (`APP_STORAGE`). `mysql` (8.0), `redis`, and `phpmyadmin` are optional via `COMPOSE_PROFILES`; external servers or the host's MySQL socket (`DB_SOCKET_DIR`) work too. The host's nginx owns the domain and HTTPS; Laravel trusts private-range proxies so signed URLs keep `https`. CI/CD (deploy script, GitLab) is intentionally not included yet.
- **Local dev:** `composer dev` runs Horizon instead of `queue:listen`; `.env` needs `QUEUE_CONNECTION=redis` and a running Redis.
- **Tickets 01–03** have no queued work (synchronous CRUD); Ticket 04 invitation emails and Ticket 05 imports are queued.

---

## 4. The Active Implementation Frontier: Next Ticket

Part 01 is complete. The frontier moves to Part 02 (Availability & Conflict Engine).

### **Part 02 / Ticket 01: Teacher Unavailability Declaration and Approval Workflow**
- **File:** [`docs/specs/02-availability-and-conflict-engine/tickets/01-teacher-unavailability-declaration.md`](file:///home/holako/github/synchro/docs/specs/02-availability-and-conflict-engine/tickets/01-teacher-unavailability-declaration.md)
- **Pending before starting:** the user plans a `/code-review` pass over all of Part 01.
- **Known follow-ups from Part 01:**
  - Temporary passwords do not yet force a password change at next login.
  - The welcome/login Fortify pages still contain pre-existing hardcoded English strings.
  - The Docker image and compose stack have not yet been built or run (Docker was unavailable in the authoring environment), and migrations have not been run against MySQL 8.0.

---

## 5. Verification Commands & Quality Checklist

Before committing changes, execute the following pipeline:

```bash
# 1. Format PHP code to project standard
vendor/bin/pint --dirty --format agent

# 2. Run the Pest feature test suite
php artisan test --compact

# 3. Check TypeScript compilation
npx tsc --noEmit

# 4. Check Vite Plus frontend lint and formatting
npx vp check resources/js

# 5. Build production frontend assets
npm run build
```

---

## 6. Recommended Skills for Next Session

Activate and consult the following skills:
- `laravel-best-practices`: For clean single actions, form requests, Eloquent relationships, and authorization gates.
- `fortify-development`: For handling authentication, password hashing, and disabling self-registration.
- `vercel-react-best-practices`: For keeping JSX lean, modularizing subcomponents, and memoizing rows.
- `wayfinder-development`: For importing typed routes (`@/actions/...` and `@/routes/...`).
- `testing-best-practices` & `tdd`: For designing resilient Pest feature tests.
- `smart-commits`: For clean, atomic Conventional Commits without co-author trailers.
