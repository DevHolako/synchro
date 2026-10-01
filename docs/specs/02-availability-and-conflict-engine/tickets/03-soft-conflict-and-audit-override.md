# 03: Soft Conflict Detection and Audit Trail Override

**What to build:** Engine logic that flags soft policy constraints (student headcount exceeding normal lecture room capacity, scheduling during a teacher's declared unavailability). Returns warning payloads that can only be saved when an authorized coordinator supplies an explicit override confirmation and an immutable justification note.

**Blocked by:** 01: Teacher Unavailability Declaration and Approval Workflow, 02: Core Conflict Detector Service for Physical Clashes

**Status:** done

- [x] Soft conflict detector: Room capacity overage (group headcount > room `course_capacity`)
- [x] Soft conflict detector: Teacher unavailability violation (slot overlaps with active teacher unavailability)
- [x] ConflictOverride model and audit table recording `user_id`, `schedulable_type`, `schedulable_id`, `conflict_type`, `justification`, and `created_at`
- [x] API and Action support for `force_override=true` with required `justification` string
- [x] Rejection with HTTP 409/422 if soft conflict is present without override flag
- [x] Automated tests asserting audit trail creation upon override and rejection when override note is missing

## Design decisions (agreed 2026-10-01)

- **Soft conflicts in scope**: room capacity overrun (sum of the session's group headcounts above the room's `course_capacity`) and teacher unavailability (any of the teacher's pending or approved unavailabilities overlapping the session: recurring ones by weekday, effective period and half-open times; ad-hoc ones by date range and times, whole days when they have none). "Outside conventional regime hours" is deferred: regime hours are not modelled (ADR 0004 treats modality as informative). The exam single-room override arrives with Part 04.
- **Responses**: hard conflicts always win (422, soft conflicts listed alongside). Soft conflicts without an override: JSON callers get **409** `{message, soft_conflicts}`; Inertia callers get translated messages under `soft_conflicts` in the errors bag, then resubmit with `force_override=true` and a `justification`.
- **Permission**: overriding needs the new `OverrideSoftConflicts` (`override:soft-conflicts`, Coordinator bundle; Administrator holds all). Sending `force_override` without it is a 403.
- **Justification**: required with `force_override`, 10–1000 characters after trimming; ignored without the flag.
- **Coverage**: an override covers every soft conflict present at save time; each one is written to the audit. Every write re-checks, so editing a session that still has a soft conflict needs a fresh override.
- **Audit (`conflict_overrides`)**: one immutable row per overridden conflict: `user_id`, `schedulable_type`, `schedulable_id` (no foreign key, so it outlives the session), `conflict_type`, `justification`, a `details` JSON snapshot (headcount vs capacity, or the unavailability and its window), `created_at`. The model refuses updates and deletes.
