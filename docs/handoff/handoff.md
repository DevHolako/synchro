# Synchro — Architectural Handoff & Implementation Frontier

## 1. Executive Summary & Repository Identity

- **Project:** Synchro — Higher Education Timetable Scheduling & Examination Logistics Platform (ISGA).
- **Remote Repository:** `https://github.com/DevHolako/synchro.git` (`origin/main`).
- **Framework & Core Stack:**
  - **Backend:** Laravel 13.34.0, PHP 8.5, Laravel Fortify (public self-registration disabled), Laravel Wayfinder.
  - **Frontend:** Inertia.js (React 19 SPA), Tailwind CSS v4, Lucide React icons.
  - **Testing & Tooling:** Pest 5.2.1, Vite Plus (`vp`), Laravel Pint.
- **Current Test Status:** 87 tests registered (84 passed, 3 skipped Fortify 2FA stubs), 377 assertions, 100% green.
- **Code Quality & Linting:**
  - TypeScript: `npx tsc --noEmit` clean (0 errors).
  - Frontend Lint: `npx vp check resources/js` passing (83 files clean, 0 errors, 0 warnings).
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

---

## 4. The Active Implementation Frontier: Next Ticket

### **Ticket 04: User Roles and Expiring Invitation Token Provisioning**
- **File:** [`docs/specs/01-core-foundation-and-referentials/tickets/04-rbac-and-invitation-token-provisioning.md`](file:///home/holako/github/synchro/docs/specs/01-core-foundation-and-referentials/tickets/04-rbac-and-invitation-token-provisioning.md)
- **Status:** `ready-for-agent`
- **Objective:** Build secure administrator-driven user onboarding without public self-registration (ADR 0007 & ADR 0011).
- **Core Requirements to Deliver:**
  1. **RBAC & Profiles:**
     - `TeacherProfile` and `StudentProfile` models linked to `User`.
     - Seeders / factories for roles and permission bundles.
  2. **Admin Provisioning & Invitation Flow:**
     - Ensure public Fortify registration route is disabled. Only users with `Permission::ManageUsers` or `Permission::ProvisionUsers` can create/invite users.
     - `InvitationToken` model with cryptographically secure token and 72-hour signed URL expiration.
     - Mailable or notification dispatched to the invited user with the signed activation link.
  3. **Activation & Password Setup:**
     - Inertia React page for setting the initial password, verifying token validity and signature prior to activation.
     - Action `ActivateUserInvitationAction` handling password setting, token consumption, and marking user active.
     - Resend invitation token action (`ResendInvitationAction`) and temporary reset action for administrators.
  4. **Pest Feature Tests:**
     - Assert unauthorized users cannot access provisioning.
     - Assert expired tokens (> 72 hours) are rejected.
     - Assert invalid or tampered tokens return 403/invalid response.
     - Assert valid tokens successfully activate account, transition state, and log the user in.
  5. **Frontend & Localization:**
     - Modular React views under `resources/js/pages/users/` or `resources/js/pages/auth/invitation.tsx`.
     - 100% translation coverage in `fr.ts` (default) and `en.ts` with zero hardcoded strings.

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
