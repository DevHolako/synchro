# 01: Teacher Unavailability Declaration and Approval Workflow

**What to build:** Interface and workflow allowing teachers to declare both recurring weekly unavailable time blocks (e.g. every Friday evening) and specific ad-hoc calendar dates. Coordinators can view, approve, or reject unavailabilities, which are registered as active soft scheduling constraints.

**Blocked by:** Part 01 - 04: User Roles and Expiring Invitation Token Provisioning

**Status:** ready-for-agent

- [ ] TeacherUnavailability model storing `teacher_id`, `type` (`recurring_weekly`, `ad_hoc_date`), `day_of_week`, `start_time`, `end_time`, `date_start`, `date_end`, `reason`, and `status` (`pending`, `approved`, `rejected`)
- [ ] Teacher portal UI for creating and managing personal unavailability requests
- [ ] Coordinator dashboard interface for reviewing, approving, or rejecting pending unavailabilities
- [ ] Automated tests asserting submission, overlapping unavailability rejection, and coordinator approval actions
