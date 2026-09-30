# Synchro — Global Specifications & Implementation Tracker

This document serves as the **Master Context & Progress Tracker** for the Synchro application. It defines the implementation roadmap across all six architectural modules, records which parts are currently pending, in progress, or completed, and tracks overall project health.

---

## 🗺️ Master Part Tracker

| Part | Spec File | Focus Area | Status | Target Seam |
| :---: | :--- | :--- | :---: | :--- |
| **01** | [`01-core-foundation-and-referentials.md`](./01-core-foundation-and-referentials.md) | Campuses, Rooms (dual capacity), Programs, Groups, Modules, CSV Import, Invitation Auth | ⏳ Pending | `ImportReferentialsAction`, `ProvisionUserAction` |
| **02** | [`02-availability-and-conflict-engine.md`](./02-availability-and-conflict-engine.md) | Conflict Engine (Hard 422 vs Soft with Audit Override), Teacher Unavailability | ⏳ Pending | `ConflictDetectorService` |
| **03** | [`03-interactive-course-planning.md`](./03-interactive-course-planning.md) | Batch Module Wizard, FullCalendar Drag-and-Drop, Attendance Register, iCal Feed | ⏳ Pending | `BatchCreateCourseSessionsAction`, FullCalendar UX |
| **04** | [`04-examination-logistics-and-convocations.md`](./04-examination-logistics-and-convocations.md) | 5-State Exam Lifecycle, Auto Room Split, Invigilators, PDF Convocations, QR Mobile Check-in | ⏳ Pending | `ScheduleExamAction`, `VerifyConvocationAction` |
| **05** | [`05-grade-entry-and-deliberations.md`](./05-grade-entry-and-deliberations.md) | Teacher Draft Grades, Deliberations, Coordinator Lock, Official Signed PV Archival | ⏳ Pending | `GradeEntryController`, `LockDeliberationAction` |
| **06** | [`06-notifications-and-mobile-api.md`](./06-notifications-and-mobile-api.md) | Driver-based Urgent Gateway (SMS/WhatsApp), In-App/Email, Versioned Sanctum REST API | ⏳ Pending | `UrgentAlertManager`, `/api/v1/` Endpoints |

*Legend: ⏳ Pending &nbsp;|&nbsp; 🔄 In Progress &nbsp;|&nbsp; ✅ Completed*

---

## 🏛️ Architectural Standards & Domain Alignments

All implementations across the six parts strictly adhere to:
1. **Domain Glossary**: Defined in [`CONTEXT.md`](../../CONTEXT.md).
2. **Architecture Decisions**: Enforced via ADRs 0001 through 0010 in [`docs/adr/`](../adr/).
3. **Dual-Engine Single-Action Architecture**:
   - 100% of business rules reside in Single-Action classes under `app/Actions/`.
   - Web controllers (`app/Http/Controllers/Web/`) invoke actions and return Inertia.js React views.
   - API controllers (`app/Http/Controllers/Api/V1/`) invoke identical actions and return Sanctum-authenticated JSON API resources.

---

## 🚀 Bootstrap & Execution Blueprint (`laravel new`)

When bootstrapping the source code:
1. Initialize the application using the official Laravel installer:
   ```bash
   # In project root or target directory
   laravel new . --react --inertia --pest
   ```
2. Configure database connections (MySQL 8.0) and install key foundational packages:
   - FullCalendar React packages (`@fullcalendar/react`, `@fullcalendar/daygrid`, `@fullcalendar/timegrid`, `@fullcalendar/interaction`)
   - QR Code & PDF generators (`simplesoftwareio/simple-qrcode`, `barryvdh/laravel-dompdf` or `spatie/browsershot`)
   - Calendar feed builder (`spatie/icalendar-generator`)
   - RBAC (`spatie/laravel-permission`)
3. Execute the implementation sequentially from Part 01 to Part 06, updating this tracker at every stage.
