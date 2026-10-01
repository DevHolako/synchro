# 01: Teacher Unavailability Declaration and Approval Workflow

**What to build:** Interface and workflow allowing teachers to declare both recurring weekly unavailable time blocks (e.g. every Friday evening) and specific ad-hoc calendar dates. Coordinators can view, approve, or reject unavailabilities, which are registered as active soft scheduling constraints.

**Blocked by:** Part 01 - 04: User Roles and Expiring Invitation Token Provisioning

**Status:** done

- [x] TeacherUnavailability model storing `teacher_id`, `type` (`recurring_weekly`, `ad_hoc_date`), `day_of_week`, `start_time`, `end_time`, `start_date`, `end_date`, `reason`, and `status` (`pending`, `approved`, `rejected`)
- [x] Teacher portal UI for creating and managing personal unavailability requests
- [x] Coordinator dashboard interface for reviewing, approving, or rejecting pending unavailabilities
- [x] Automated tests asserting submission, overlapping unavailability rejection, and coordinator approval actions

## Design decisions (agreed 2026-10-01)

- **Shapes**: a *recurring weekly* block has a required `day_of_week` (ISO 1 = Monday … 7 = Sunday), required `start_time`/`end_time`, a required `start_date` and an optional `end_date` (its effective period; open-ended when empty). An *ad-hoc* unavailability is a date range (`start_date`–`end_date`, both required) with an optional daily time window; no times means whole days. `start_time`/`end_time` are both set or both empty.
- **Validation**: `start_date` is today or later (create and edit). Times use 15-minute steps inside the 08:00–22:00 grid (ADR 0004) and `start_time < end_time`. `reason` is required (max 500 characters); the form warns that coordinators read it.
- **Overlap rejection**: a new or edited request is rejected when it overlaps one of the same teacher's *pending or approved* requests **of the same type** (rejected ones are ignored; an edit ignores itself). Intervals are half-open, so 10:00–12:00 and 12:00–14:00 do not overlap. Recurring blocks overlap when they share a weekday and both their periods and their times intersect. Recurring vs ad-hoc is not compared: the redundancy is harmless and the conflict engine reports both.
- **Lifecycle**: `pending` → `approved` | `rejected`, decided once by a reviewer (decisions are final; only pending requests can be reviewed). The teacher can edit a request only while pending and can delete it in any status (removing a constraint needs no approval). To change a rejected request, the teacher submits a new one.
- **Review**: `reviewed_by`, `reviewed_at`, `review_note`; the note is required to reject, optional to approve, and visible to the teacher.
- **Permissions**: `DeclareUnavailability` (`declare:unavailability`, Teacher bundle) and `ReviewUnavailability` (`review:unavailability`, Coordinator bundle); Administrator holds both. Edit/delete also require owning the request.
- **Notifications**: none in this ticket (Part 06). Reviewers see a pending-count badge in the sidebar.
- **Out of scope**: coordinators declaring on a teacher's behalf; `/api/v1` endpoints (Part 06).
- **Data**: `teacher_id` references `users` (cascade on delete); `reviewed_by` is nulled on delete; index on `(teacher_id, status)`. Submissions lock the teacher's `users` row so concurrent requests cannot both pass the overlap check.
