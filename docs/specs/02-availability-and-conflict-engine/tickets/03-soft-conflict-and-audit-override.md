# 03: Soft Conflict Detection and Audit Trail Override

**What to build:** Engine logic that flags soft policy constraints (student headcount exceeding normal lecture room capacity, scheduling during a teacher's declared unavailability). Returns warning payloads that can only be saved when an authorized coordinator supplies an explicit override confirmation and an immutable justification note.

**Blocked by:** 01: Teacher Unavailability Declaration and Approval Workflow, 02: Core Conflict Detector Service for Physical Clashes

**Status:** ready-for-agent

- [ ] Soft conflict detector: Room capacity overage (group headcount > room `course_capacity`)
- [ ] Soft conflict detector: Teacher unavailability violation (slot overlaps with active teacher unavailability)
- [ ] ConflictOverride model and audit table recording `user_id`, `schedulable_type`, `schedulable_id`, `conflict_type`, `justification`, and `created_at`
- [ ] API and Action support for `force_override=true` with required `justification` string
- [ ] Rejection with HTTP 409/422 if soft conflict is present without override flag
- [ ] Automated tests asserting audit trail creation upon override and rejection when override note is missing
