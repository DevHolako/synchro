# 04: Automated Retake Session (Rattrapage) Candidate Roster

**What to build:** Automated identification and grouping of students with failing grades (final score < 10/20) upon deliberation locking, pre-populating candidate rosters for Retake Exam scheduling without manual calculations.

**Blocked by:** 03: Coordinator Deliberation Review, Official PV PDF Archival, and Grade Locking

**Status:** ready-for-agent

- [ ] Automatic identification query filtering candidates with `final_grade < 10.00` upon deliberation lock
- [ ] Retake candidate roster view for coordinators per module and group
- [ ] One-click action to create a Retake Exam in the corresponding Retake Exam Period pre-enrolled with failing students
- [ ] Automated tests asserting accurate student filtering based on passing thresholds and retake exam population
