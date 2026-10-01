# Synchro — Architectural Handoff & Implementation Frontier

## 1. Executive Summary & Repository Identity

- **Project:** Synchro — Higher Education Timetable Scheduling & Examination Logistics Platform (ISGA).
- **Remote Repository:** `https://github.com/DevHolako/synchro.git` (`origin/main`).
- **Framework & Core Stack:**
  - **Backend:** Laravel 13.34.0, PHP 8.5, Laravel Fortify (public self-registration disabled), Laravel Wayfinder.
  - **Frontend:** Inertia.js (React 19 SPA), Tailwind CSS v4, Lucide React icons.
  - **Queues & Infrastructure:** Redis queues supervised by Laravel Horizon; production ships as a Docker Compose stack (FrankenPHP/Caddy app, Horizon, scheduler, MySQL 8.0, Redis) behind the host's nginx — see ADR 0012.
  - **Testing & Tooling:** Pest 5.2.1, Vite Plus (`vp`), Laravel Pint.
- **Current Test Status:** `composer test` green on 2026-10-01: Pint, PHPStan level 7 (0 errors), and Pest with 211 tests (206 passed, 5 skipped: 3 Fortify 2FA stubs + 2 legacy registration tests skipped because registration is disabled), 991 assertions.
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
   - Implemented: `ImportType` enum (`rooms`, `modules`, `teachers`, `students`) defining columns, required columns, template example rows, and the policy ability; `ImportReferentialsAction` maps each type to its row importer (generic `RowImporter<TPayload>`, so each payload is typed for its action); `SpreadsheetReader` (first sheet of `.csv`/`.xlsx` via `openspout/openspout`, auto-detects `,`/`;`/tab and Windows-1252, keeps real row numbers, max 2000 rows).
   - Actions: `ImportReferentialsAction` validates every row first (rules, in-file duplicates, reference resolution) and only then writes all rows in one transaction; any failure rolls everything back. Row importers (`app/Actions/Imports/Importers/`) delegate to `CreateRoomAction`, `CreateModuleAction`, and `ProvisionUserAction`.
   - Queued processing: `QueueSpreadsheetImportAction` stores the upload and creates a `SpreadsheetImport` record (`pending`), then `ProcessSpreadsheetImportJob` (queue `imports`, 1 try, 600s timeout) calls `RunSpreadsheetImportAction`, which claims the record (`pending` → `processing`, so redelivery is a no-op), runs `ImportReferentialsAction`, stores the outcome and row errors, and deletes the file. `failed()` records crashes/timeouts.
   - Invitations: `ProvisionUserAction` issues invitations via `DB::afterCommit`, and `UserInvitationNotification` is queued on `notifications`, so a rolled-back import sends no emails.
   - Permissions: new `ImportReferentials` (`import:referentials`) held by Administrator and Coordinator; each type additionally requires its create ability (coordinators can import rooms/modules, not accounts).
   - UI: `resources/js/pages/imports/` wizard (type picker, column guide with CSV template download, drag-and-drop zone) plus a recent-imports history that polls (`usePoll`) while an import is queued/processing and shows row-level errors.

### Completed Tickets in Part 02 (Availability & Conflict Engine)
1. **Ticket 01: Teacher Unavailability Declaration and Approval Workflow** ([`01-teacher-unavailability-declaration.md`](file:///home/holako/github/synchro/docs/specs/02-availability-and-conflict-engine/tickets/01-teacher-unavailability-declaration.md), design decisions recorded in the ticket)
   - Implemented: `TeacherUnavailability` (`teacher_unavailabilities`), `UnavailabilityType` (`recurring_weekly`, `ad_hoc_date`), `UnavailabilityStatus` (`pending`, `approved`, `rejected`; `active()` = pending + approved).
   - Permissions: `DeclareUnavailability` (Teacher) and `ReviewUnavailability` (Coordinator); `TeacherUnavailabilityPolicy` adds ownership for edit/delete.
   - Business Rules: same-type overlap rejection against the teacher's active requests (`TeacherUnavailability::scopeOverlapping()`, half-open times, inclusive dates, open-ended recurring periods) under a lock on the teacher's `users` row; edit only while pending, delete in any status; decisions are final (conditional update on `pending`), rejection needs a note; start dates today or later; quarter-hour times inside 08:00–22:00.
   - Actions: `DeclareUnavailabilityAction`, `UpdateUnavailabilityAction`, `DeleteUnavailabilityAction`, `ReviewUnavailabilityAction`, `GuardUnavailabilityOverlapAction`.
   - UI: `resources/js/pages/unavailabilities/` (teacher: current/past, create/edit dialog, withdraw) and `resources/js/pages/unavailability-reviews/` (coordinator: status counts, filters, approve/reject dialog). Sidebar links are permission-gated; the review link shows the shared `pendingUnavailabilityCount` badge.
   - For Ticket 03: the soft-conflict detector should query `TeacherUnavailability::active()` for the session's teacher.

2. **Ticket 02: Core Conflict Detector Service for Physical Clashes** ([`02-synchronous-conflict-detector-service.md`](file:///home/holako/github/synchro/docs/specs/02-availability-and-conflict-engine/tickets/02-synchronous-conflict-detector-service.md), design decisions recorded in the ticket)
   - Implemented: `CourseSession` (`course_sessions`: module, teacher, room, `starts_at`/`ends_at`) linked to one or more groups through `course_session_student_group`; `ConflictType` enum. Teachers, rooms and modules with sessions cannot be deleted.
   - Engine (`app/Services/Scheduling/`): `ConflictDetectorService::checkConflicts(SessionSlot): ConflictResult`, fed by `OccupancySource` implementations (`CourseSessionOccupancy` now; exams join in Part 04). One indexed query per resource type (room, teacher, groups); half-open intervals; same-day lookups bounded on `starts_at`.
   - Actions: `CreateCourseSessionAction`, `UpdateCourseSessionAction`, `DeleteCourseSessionAction`, `GuardSessionConflictsAction` (locks rooms → users → groups, then checks). `HardConflictException` renders 422: structured JSON for JSON callers, translated `conflicts` errors for Inertia.
   - HTTP (no UI yet): `POST /course-sessions`, `PUT`/`DELETE /course-sessions/{session}`, `POST /course-sessions/check` (JSON, saves nothing). `CourseSessionPolicy` uses `ManageSchedules` / `ViewSchedules`.
   - Validation: active module/room/groups, teacher role (defaults to the module's teacher), groups in the module's program, same day inside 08:00–22:00 on quarter hours; past dates allowed.
   - Benchmark: `php artisan conflicts:benchmark --sessions=N` (rolled back). Local SQLite: 5,000 sessions → 2.4 ms average / 4.0 ms p95; 30,000 → 6.8 ms / 10.0 ms.

### Cross-cutting: Queues, Horizon & Docker Compose (ADR 0012)
- **Queues:** `notifications` + `default` (supervisor `supervisor-default`, 3 tries with backoff, 60s) and `imports` (supervisor `supervisor-imports`, 1 try, 630s). `REDIS_QUEUE_RETRY_AFTER` = 700. Horizon dashboard at `/horizon`, gated by `Permission::MonitorQueues` (Administrator).
- **Scheduler** (`routes/console.php`): `horizon:snapshot` every 5 minutes, `imports:fail-stale` every 15 minutes (fails imports pending for 6 hours or processing 20 minutes past the job timeout), `queue:prune-failed --hours=168` and `model:prune` daily (finished `SpreadsheetImport` after 90 days, unusable `InvitationToken` after 30 days except each user's latest).
- **Docker** (modelled on the we-cretif setup): `Dockerfile` (FrankenPHP `dunglas/frankenphp:1-php8.5`, composer and node builder stages, target `production`), `docker-compose.yml`, `docker/Caddyfile`, `docker/entrypoint.sh`, `docker/hestia/synchro.{tpl,stpl}` (host nginx templates), `.env.docker.example`. `app` serves HTTP on `127.0.0.1:${APP_PORT:-8000}` (Caddy `auto_https off`) and migrates on boot; `app`, `horizon`, and `scheduler` share the `synchro:latest` image and the storage volume (`APP_STORAGE`). `mysql` (8.0), `redis`, and `phpmyadmin` are optional via `COMPOSE_PROFILES`; external servers or the host's MySQL socket (`DB_SOCKET_DIR`) work too. The host's nginx owns the domain and HTTPS; Laravel trusts private-range proxies so signed URLs keep `https`. CI/CD (deploy script, GitLab) is intentionally not included yet.
- **Local dev:** `composer dev` runs Horizon instead of `queue:listen`; `.env` needs `QUEUE_CONNECTION=redis` and a running Redis.
- **Tickets 01–03** have no queued work (synchronous CRUD); Ticket 04 invitation emails and Ticket 05 imports are queued.

---

## 4. The Active Implementation Frontier: Next Ticket

Part 01 is complete. Part 02 (Availability & Conflict Engine) is in progress: Tickets 01 and 02 are done.

### **Part 02 / Ticket 03: Soft Conflict Detection and Audit Trail Override**
- **File:** [`docs/specs/02-availability-and-conflict-engine/tickets/03-soft-conflict-and-audit-override.md`](file:///home/holako/github/synchro/docs/specs/02-availability-and-conflict-engine/tickets/03-soft-conflict-and-audit-override.md)
- Tickets 01 and 02 are done. Soft conflicts plug into `ConflictResult::$softConflicts`: capacity overrun (sum of the session's group headcounts vs `course_capacity`) and teacher unavailability (`TeacherUnavailability::active()` matched against the session's weekday/date and times). Discuss the design first (override flag and justification shape, 409 vs 422, the audit table, how Inertia shows the warning).
- **Code reviews:** the post-Part-01 work (`2b61777..265229c`) was reviewed and all 10 findings fixed (`dcb7957..a08b416`). The review of Part 01's own tickets (`523cfbe..2b61777`, judged against current code) ran on 2026-10-01. Fixed right away:
  - Partial updates skipped scoped-uniqueness and capacity checks (moving a room/building/program/group/module to another parent, or lowering only `course_capacity`), which ended in a 500 instead of a 422. Update requests now `mergeIfMissing` the stored values in `prepareForValidation()`.
  - Modules accepted any user as teacher on the web path; requests and actions now require a teacher.
  - Hardcoded English 403 and validation messages, hardcoded URLs instead of Wayfinder, double success toasts (server flash + client toast), untranslated breadcrumbs, raw permission strings in the sidebar, unused `User::canManageReferentials()`.
- **Known follow-ups from Part 01:**
  - Temporary passwords do not yet force a password change at next login.
  - The welcome/login Fortify pages still contain pre-existing hardcoded English strings.
  - The Docker image and compose stack have not yet been built or run (Docker was unavailable in the authoring environment), and migrations have not been run against MySQL 8.0.
- **Deferred findings from the Part 01 review (not blocking):**
  - `academic-structure/index.tsx` (246 lines) and `rooms/index.tsx` (230 lines) exceed the page ceiling; row memoization is defeated by inline callbacks and object props, and rows live in `*-table.tsx` instead of `*-row.tsx`.
  - Duplicated code: three near-identical toggle handlers on the academic page, room capacity checks repeated in the Create/Update actions (untranslated `InvalidArgumentException`s), the `name LIKE / code LIKE` search in 4 index controllers. `RoomIndexController` and `AcademicStructureIndexController` build filters and stats inline.
  - Primitive obsession: modality `in_array` in `AcademicStructureIndexController`, `role === 'teacher'` in `provision-user-dialog.tsx`; the room filters are 8 states passed as 18 props.
  - Starter-kit footer links in the sidebar; duplicated language-switcher buttons.
  - No `lang/fr/validation.php`, so Laravel's built-in validation messages still display in English.
  - Backend messages (flash toasts, validation errors) follow `APP_LOCALE`, not the UI language switcher, which is client-side only (`localStorage`). `.env.example` ships `APP_LOCALE=en`, so a local install shows English toasts in the French UI; `.env.docker.example` uses `fr`.
  - Spec gaps to decide later: a `suspended` account status, a room board-type field, and the `/api/v1` controllers of ADR 0009 (planned with Part 06). Coordinators keep import access for rooms and modules (deliberate).

---

## 5. Verification Commands & Quality Checklist

Policy: `.agents/rules/mandatory-verification-tests.md`. Steps 1, 3 and 4 plus *targeted* Pest tests (`--filter`) run before finishing any change; the full suite (step 2 unfiltered, or `composer test` / `composer ci:check`) and the build (step 5) run only when the user asks.

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
