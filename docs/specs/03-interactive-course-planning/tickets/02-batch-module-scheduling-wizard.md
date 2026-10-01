# 02: Batch Module Scheduling Wizard for Intensive Blocks

**What to build:** Step-by-step wizard enabling coordinators to batch-schedule all sessions of an intensive modular block across target dates (e.g. 3 consecutive Saturdays 08:30–17:30) in a single atomic transaction with conflict checks across all dates.

**Blocked by:** 01: React FullCalendar Timetable Views with Open Operational Grid, Part 02 - 02: Core Conflict Detector Service for Physical Clashes

**Status:** done

- [x] Batch scheduler modal UI allowing date pattern selection (consecutive weekends, specific days)
- [x] Single-Action `BatchCreateCourseSessionsAction` executing in a database transaction
- [x] Multi-slot conflict pre-check flagging any clashing dates prior to database write
- [x] Full rollback if any single slot encounters an un-bypassed conflict
- [x] Dynamic update of the syllabus hours depletion meter upon successful creation
- [x] Automated tests asserting atomic batch creation and rollback on collision

**Note (2026-10-01):** the `CourseSession` model (sessions linked to several groups), `CreateCourseSessionAction` and the hard-conflict detector already exist from Part 02 / Ticket 02; the wizard should call them rather than create its own model.

## Design decisions (agreed 2026-10-01)

- **Dates**: a recurrence rule (weekdays, a start date, then a number of dates or an end date) generates the date list on the client; the user then removes or adds dates by hand. The server only ever receives explicit slots (`slots: [{starts_at, ends_at}]` in `Y-m-d H:i`).
- **Day template**: one or more time ranges applied to every date; each range becomes its own session (so a 08:30–12:30 + 13:30–17:30 day does not count the lunch break as teaching).
- **Shared assignment**: one module, teacher (defaults to the module's), room and set of groups (from the module's program) for the whole batch. Per-session changes come afterwards (ticket 03).
- **Check, then save**: `POST /course-sessions/batch/check` (JSON, writes nothing) reports each slot's hard and soft conflicts with saved bookings, the other slots of the batch it overlaps (found in memory), and a syllabus meter per group. `POST /course-sessions/batch` saves every slot through `SaveCourseSessionAction` in one transaction, so each slot is locked and re-checked and the batch's own slots collide like any double booking.
- **All or nothing**: the first slot with a conflict that is not overridden rolls the batch back. `BatchConflictException`: JSON callers get 422 (hard) or 409 (soft) with the slot (`index`, times) and its conflicts; Inertia callers get the messages under `slots`. The wizard never skips slots: the user removes the clashing ones in the review step.
- **Override**: one `force_override` + justification covers every soft conflict of the batch; the audit still has one row per conflict per session, all with that justification. Needs `OverrideSoftConflicts` (403 otherwise). Users without it see why they are blocked and must remove the slots.
- **Limits and validation**: 1 to 60 slots; two slots with the same start are rejected; each slot follows the single-session rules (same day, inside the grid, quarter hours; past dates allowed, as for single sessions).
- **Syllabus meter**: in the review step, per selected group: hours already planned for the module plus the batch's hours against `total_hours`. Going over is a warning, not a soft conflict.
- **Where**: a "Schedule sessions" button on the timetable (users with `ManageSchedules`) opens a 3-step dialog (module and resources, dates and times, review). It pre-fills the group, room or teacher of the current perspective, the module highlighted in the syllabus panel, and the displayed period as start date. It doubles as single-session creation. The options (`schedulingOptions`: active modules, groups with headcount, rooms with building and capacity, teachers) are an optional Inertia prop loaded the first time the dialog opens. Rooms smaller than the groups' headcount show a warning.
- **After saving**: toast "N séances planifiées", the dialog closes and the calendar jumps to the first new session's week, keeping the perspective. If the save fails after a clean check (someone booked in between), nothing is saved, the wizard stays on the review step and re-runs the check.
- **No batch identity**: sessions do not remember the batch that created them.
- **One scheduling grid**: `App\Support\SchedulingGrid` (PHP) and `resources/js/lib/scheduling-grid.ts` now hold the 08:00–22:00 quarter-hour grid for the session, batch and unavailability requests, their messages, the calendar and the time inputs.
