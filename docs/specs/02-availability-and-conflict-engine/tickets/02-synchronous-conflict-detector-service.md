# 02: Core Conflict Detector Service for Physical Clashes

**What to build:** High-performance, synchronous collision detection service evaluating physical resource clashes in under 80 milliseconds. Rejects simultaneous bookings of the same Teacher, Room, or Student Group with a strict HTTP 422 Hard Conflict response.

**Blocked by:** Part 01 - 01: Campus, Building, and Room Infrastructure with Dual Capacities, Part 01 - 02: Academic Hierarchy

**Status:** done

- [x] `ConflictDetectorService` exposing unified method `checkConflicts(targetData): ConflictResult`
- [x] Hard conflict detector: Room collision (already occupied in overlapping time window)
- [x] Hard conflict detector: Teacher collision (already assigned to another active session or exam)
- [x] Hard conflict detector: Student Group collision (group scheduled for another simultaneous session)
- [x] Sub-80ms execution benchmark using optimized composite database indexes
- [x] Automated tests covering edge-case boundary intervals (adjacent slots without overlap vs 1-minute overlap)

## Design decisions (agreed 2026-10-01)

- **Scope**: this ticket creates the minimal `CourseSession` model the detector checks against (Part 03 adds the calendar, batch wizard, drag-and-drop and attendance on top). Only course sessions are checked; exams join in Part 04 as a second occupancy source behind the same `OccupancySource` seam.
- **Session shape**: `module_id`, `teacher_id` (users), `room_id`, `starts_at`, `ends_at`, and one or more student groups through `course_session_student_group` (a shared lecture serves several groups). No status yet: removing a session deletes it.
- **Time rules**: same day, inside the 08:00–22:00 grid (ADR 0004), quarter-hour steps, `ends_at` after `starts_at`; past dates are allowed so records can be corrected. Intervals are half-open (10:00–12:00 and 12:00–14:00 do not conflict).
- **Validation (422, not conflicts)**: module, room and groups must be active; the teacher must be a teacher (defaults to the module's teacher, may differ); every group must belong to the module's program.
- **Hard conflicts**: room, teacher, or any shared group already booked in an overlapping session. Writes run in a transaction that locks the room, teacher and group rows (rooms → users → groups, by id) before checking. A conflict raises `HardConflictException`: JSON callers get `{message, conflicts: [{type, resource_id, resource_name, session_id, starts_at, ends_at}]}`, Inertia callers get translated messages under `conflicts` in the errors bag.
- **HTTP**: create, update, delete, and a non-persisting `check` endpoint (for Part 03's drag-and-drop); no UI in this ticket. `ManageSchedules` gates writes and checks, `ViewSchedules` reading.
- **Deletes**: teachers, rooms and modules with sessions cannot be deleted (deactivate them instead); deleting a group only unlinks it.
- **Performance**: tests assert one query per resource type (3 per check) and the composite indexes `(room_id, starts_at)`, `(teacher_id, starts_at)` and `(student_group_id)`; `php artisan conflicts:benchmark` measures real timings on a seeded dataset outside the suite.
