# Synchro — Global Specifications & Implementation Tracker

This document serves as the **Master Context & Progress Tracker** for the Synchro application. It defines the implementation roadmap across all six architectural modules, records which parts are currently pending, in progress, or completed, and tracks overall project health.

---

## 🗺️ Master Part Tracker

| Part | Specification & Tickets | Focus Area | Status | Target Seam |
| :---: | :--- | :--- | :---: | :--- |
| **01** | [`spec.md`](./01-core-foundation-and-referentials/spec.md) &nbsp;•&nbsp; [**5 Tickets**](./01-core-foundation-and-referentials/tickets/) | Campuses, Rooms (dual capacity), Programs, Groups, Modules, CSV Import, Invitation Auth | ✅ Completed | `ImportReferentialsAction`, `ProvisionUserAction` |
| **02** | [`spec.md`](./02-availability-and-conflict-engine/spec.md) &nbsp;•&nbsp; [**3 Tickets**](./02-availability-and-conflict-engine/tickets/) | Conflict Engine (Hard 422 vs Soft with Audit Override), Teacher Unavailability | 🔄 In progress (1/3) | `ConflictDetectorService` (< 80ms) |
| **03** | [`spec.md`](./03-interactive-course-planning/spec.md) &nbsp;•&nbsp; [**5 Tickets**](./03-interactive-course-planning/tickets/) | Batch Module Wizard, FullCalendar Drag-and-Drop, Attendance Register, iCal Feed | ⏳ Pending | `BatchCreateCourseSessionsAction`, FullCalendar UX |
| **04** | [`spec.md`](./04-examination-logistics-and-convocations/spec.md) &nbsp;•&nbsp; [**5 Tickets**](./04-examination-logistics-and-convocations/tickets/) | 5-State Exam Lifecycle, Auto Room Split, Invigilators, PDF Convocations, QR Mobile Check-in | ⏳ Pending | `ScheduleExamAction`, `VerifyConvocationAction` |
| **05** | [`spec.md`](./05-grade-entry-and-deliberations/spec.md) &nbsp;•&nbsp; [**4 Tickets**](./05-grade-entry-and-deliberations/tickets/) | Teacher Draft Grades, Deliberations, Coordinator Lock, Official Signed PV Archival | ⏳ Pending | `GradeEntryController`, `LockDeliberationAction` |
| **06** | [`spec.md`](./06-notifications-and-mobile-api/spec.md) &nbsp;•&nbsp; [**4 Tickets**](./06-notifications-and-mobile-api/tickets/) | Driver-based Urgent Gateway (SMS/WhatsApp), In-App/Email, Versioned Sanctum REST API | ⏳ Pending | `UrgentAlertManager`, `/api/v1/` Endpoints |

*Total: 6 Specifications, 26 Vertical Slice Tickets*

---

## 🏛️ Architectural Standards & Domain Alignments

All implementations across the six parts strictly adhere to:
1. **Domain Glossary**: Defined in [`CONTEXT.md`](../../CONTEXT.md).
2. **Architecture Decisions**: Enforced via ADRs 0001 through 0011 in [`docs/adr/`](../adr/).
3. **Dual-Engine Single-Action Architecture**:
   - 100% of business rules reside in Single-Action classes under `app/Actions/`.
   - Web controllers (`app/Http/Controllers/Web/`) invoke actions and return Inertia.js React views.
   - API controllers (`app/Http/Controllers/Api/V1/`) invoke identical actions and return Sanctum-authenticated JSON API resources.
4. **Strict Permission-Based Authorization (ADR 0011)**:
   - Permissions (`App\Enums\Permission`) are the sole gate of check across policies, form requests, and gates.
   - Roles (`App\Enums\UserRole`) are strictly groupings/bundles of permissions and must never be evaluated directly as authorization gates.
5. **Vercel React Best Practices & Modular UI**:
   - Strictly follow `/vercel-react-best-practices`.
   - Zero monolithic JSX pages (> 150-200 lines). Every feature page must be decomposed into modular subcomponents under `resources/js/pages/{feature}/components/`.

---

## 🚀 Bootstrap & Execution Blueprint

Execute the implementation sequentially from Part 01 to Part 06, picking up tickets along the dependency frontier and updating this tracker at every stage.
