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

## 🧪 5. Testing & Verification Workflow

- **Pest Tests**: Run targeted tests with `php artisan test --compact --filter=TestName`.
- **PHP Code Formatter**: Run `vendor/bin/pint --dirty --format agent` before finalizing changes.
- **Frontend Typecheck**: Run `npm run types:check`.
- **Frontend Build**: Run `npm run build`.

---

## 📦 6. Git Commit Standards

- Follow Conventional Commits: `<type>(<scope>): <subject>` (`feat`, `fix`, `test`, `docs`, `refactor`, `chore`).
- Do not use `Co-Authored-By:` tags.
- Commit in clean, dependency-ordered layers (migrations/models $\rightarrow$ actions/controllers $\rightarrow$ UI $\rightarrow$ tests $\rightarrow$ docs).
