# 03: Interactive Drag-and-Drop Rescheduling with Conflict Feedback

**What to build:** Drag-and-drop and resize capabilities on FullCalendar. When dropped, the session calls the conflict engine. Hard conflicts instantly snap the event back to its original slot with an error toast; soft conflicts open a confirmation modal requiring an audit justification.

**Blocked by:** 01: React FullCalendar Timetable Views with Open Operational Grid, Part 02 - 03: Soft Conflict Detection and Audit Trail Override

**Status:** done

- [x] FullCalendar event drag and resize handlers connected to Inertia / API endpoints
- [x] Asynchronous conflict verification upon drop
- [x] Optimistic snapback handling reverting DOM position if a Hard Conflict (HTTP 422) is returned
- [x] Soft conflict confirmation modal presenting warning details and an audit comment input
- [x] Persistent update and success toast upon validated drop or authorized override
- [x] Automated tests asserting move/resize controller endpoints and snapback payload structures

## Design decisions (agreed 2026-10-02)

- **Endpoint**: `PATCH /course-sessions/{session}/reschedule` takes only `starts_at`, `ends_at` (and `force_override` + `justification`) and answers in JSON: 200 with the moved session, 422 hard conflict, 409 soft conflict without override. The module, teacher, room and groups are kept and not re-validated as active, so a session of a since-deactivated room can still move. It saves through `SaveCourseSessionAction` (same locks, re-check and audit). The hard-conflict JSON now also carries Laravel's `errors` shape, the only part of a 422 that Inertia's `useHttp` keeps.
- **No separate check**: a drop saves straight away. Hard conflict: the event snaps back with a toast. Soft conflict: nothing is written, a modal lists every warning (in the UI language) with a justification field; confirming re-sends with the override, cancelling snaps back. Users without `OverrideSoftConflicts` can only cancel.
- **Who and where**: `ManageSchedules`, grid views only (lists stay read-only), not on phones. Moves and resizes snap to 15 minutes; resizing changes the end only; the month view moves dates but does not resize. While a move saves, the event is dimmed and nothing else can move.
- **The past is locked**: sessions that have started cannot be moved or deleted, and nothing can be dropped before now (enforced by the calendar, the reschedule request and `DeleteCourseSessionAction`). Correcting a past session still goes through the full update route.
- **Delete**: the details dialog gets "Supprimer la séance" with a confirmation, for sessions that have not started.
- **Other people's changes**: `usePoll` reloads `sessions` and `syllabus` every 30 seconds, paused during a drag, a pending save, the soft-conflict modal and the scheduling wizard. Reverb was considered and deferred (see the handoff) to keep the delivery simple.
