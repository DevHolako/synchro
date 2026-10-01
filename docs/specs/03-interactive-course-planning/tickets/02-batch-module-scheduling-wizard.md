# 02: Batch Module Scheduling Wizard for Intensive Blocks

**What to build:** Step-by-step wizard enabling coordinators to batch-schedule all sessions of an intensive modular block across target dates (e.g. 3 consecutive Saturdays 08:30–17:30) in a single atomic transaction with conflict checks across all dates.

**Blocked by:** 01: React FullCalendar Timetable Views with Open Operational Grid, Part 02 - 02: Core Conflict Detector Service for Physical Clashes

**Status:** ready-for-agent

- [ ] Batch scheduler modal UI allowing date pattern selection (consecutive weekends, specific days)
- [ ] Single-Action `BatchCreateCourseSessionsAction` executing in a database transaction
- [ ] Multi-slot conflict pre-check flagging any clashing dates prior to database write
- [ ] Full rollback if any single slot encounters an un-bypassed conflict
- [ ] Dynamic update of the syllabus hours depletion meter upon successful creation
- [ ] Automated tests asserting atomic batch creation and rollback on collision

**Note (2026-10-01):** the `CourseSession` model (sessions linked to several groups), `CreateCourseSessionAction` and the hard-conflict detector already exist from Part 02 / Ticket 02; the wizard should call them rather than create its own model.
