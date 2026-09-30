# Spec 02: Availability & Anti-Conflict Engine

## Problem Statement

Schedulers frequently encounter painful resource clashes: professors assigned to teach two groups at the same hour, two classes scheduled into the same amphitheatre, or courses placed during an instructor's known external professional obligations. Detecting these clashes manually across dozens of concurrent courses is mathematically unreliable and causes last-minute cancellations.

## Solution

A high-performance, real-time Conflict Detector Engine that deterministically classifies potential collisions into non-bypassable Hard Conflicts (physical overlap of Teacher, Room, or Student Group) and overrideable Soft Conflicts (capacity overage, declared teacher unavailability). Hard Conflicts reject the operation synchronously with HTTP 422, while Soft Conflicts provide structured warning payloads that can only be saved when an authorized coordinator supplies an explicit override flag and an immutable audit trail comment.

## User Stories

1. As a Teacher, I want to declare my weekly recurring unavailable time slots, so that coordinators are aware of my permanent constraints.
2. As a Teacher, I want to declare ad-hoc unavailable dates (e.g. business trips or conferences), so that coordinators do not schedule sessions during those specific periods.
3. As a Coordinator, I want to view all submitted teacher unavailabilities with their status (`Pending` or `Approved`), so that I can validate or negotiate them.
4. As a Coordinator, I want the system to immediately prevent me from double-booking a Teacher for two course sessions or exam invigilations at the same time, so that physical conflicts are impossible.
5. As a Coordinator, I want the system to immediately prevent me from booking an already occupied Room for another session, so that room clashes are eliminated.
6. As a Coordinator, I want the system to immediately prevent me from booking two simultaneous sessions for the same Student Group, so that students never have conflicting classes.
7. As a Coordinator, I want the system to detect when a Student Group's headcount exceeds a room's Course Capacity and warn me with a Soft Conflict modal, so that I can choose whether to override or select a larger room.
8. As a Coordinator, I want the system to warn me if I attempt to schedule a Teacher during their declared unavailability window, so that I can override it with an audit note if verbal agreement was reached.
9. As an Administrator, I want all soft conflict overrides to be recorded in an immutable Audit Log with the user ID, timestamp, and justification comment, so that administrative decisions remain accountable.
10. As a Developer, I want conflict checks to execute in under 80 milliseconds, so that drag-and-drop operations on the frontend calendar remain instant and fluid.

## Implementation Decisions

- **Conflict Classification (ADR 0002)**:
  - **Hard Conflicts (HTTP 422 - Non-bypassable)**:
    - Teacher collision (active course session or exam invigilation in overlapping window).
    - Room collision (active course session or exam room allocation in overlapping window).
    - Student Group collision (another course session or exam scheduled for the same group).
  - **Soft Conflicts (HTTP 409/Warning - Overrideable)**:
    - Room capacity overrun (group size > room `course_capacity`).
    - Teacher declared unavailability (overlapping with `Pending` or `Approved` unavailability).
    - Schedule outside conventional regime hours.
- **Service Seam**: Centralized `ConflictDetectorService` exposing a unified method:
  `checkConflicts(sessionData): ConflictResult`
  returning a typed DTO containing `hasHardConflicts`, `hardConflicts[]`, `hasSoftConflicts`, `softConflicts[]`.
- **Audit Logging**: Any write operation containing `force_override=true` persists a record into `conflict_overrides` containing `user_id`, `schedulable_type`, `schedulable_id`, `conflict_type`, `justification`, and `created_at`.
- **Teacher Unavailability Structure**: Stored in `teacher_unavailabilities` with `type` (`recurring_weekly` vs `ad_hoc_date`), `day_of_week`, `start_time`, `end_time`, `start_date`, `end_date`, `reason`, and `status` (`pending`, `approved`, `rejected`).

## Testing Decisions

- **Seam**: Direct unit and integration tests against `ConflictDetectorService` with mocked and real database fixtures.
- **Coverage**:
  - Overlapping time calculations: exact boundary edges (e.g. 10:00–12:00 vs 12:00–14:00 must NOT conflict; 10:00–12:00 vs 11:59–13:00 MUST conflict).
  - Multi-resource collision matrices (Teacher, Room, Group simultaneous checks).
  - Soft conflict persistence rejection without override flag, and success with override flag + audit trail generation.
  - Performance benchmark asserting execution time remains well below 80ms under high dataset load.

## Out of Scope

- Heuristic automated timetable generation algorithms (AI/genetic solvers that auto-place hundreds of courses blindly).
- Geolocation transit time calculation between separate campus sites.

## Further Notes

- Query optimizations must utilize indexed composite keys on `(room_id, start_time, end_time)` and `(teacher_id, start_time, end_time)` to satisfy sub-80ms performance criteria.
