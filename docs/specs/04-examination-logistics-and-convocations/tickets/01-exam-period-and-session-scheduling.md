# 01: Exam Period Definition and 5-State Exam Scheduling

**What to build:** Comprehensive examination planning module. Coordinators define Exam Periods (Normal Session and Retake Session) and schedule exams across the 5-state lifecycle (`Draft`, `Scheduled`, `Published`, `Completed`, `Archived`), keeping provisional exams hidden from students until published.

**Blocked by:** Part 01 - 03: Module Catalog with Syllabus Hours and Color Coding, Part 02 - 02: Core Conflict Detector Service

**Status:** ready-for-agent

- [ ] ExamPeriod model storing `name`, `session_type` (`normal`, `rattrapage`), `start_date`, and `end_date`
- [ ] Exam model linking `exam_period_id`, `module_id`, `group_id`, `date`, `start_time`, `end_time`, and `state` (`draft`, `scheduled`, `published`, `completed`, `archived`)
- [ ] Explicit state machine transitions with validation guards
- [ ] Exam calendar and listing view for coordinators to manage exam timetables
- [ ] Student access control restricting visibility strictly to `Published`, `Completed`, and `Archived` exams
- [ ] Automated tests asserting lifecycle transitions and student authorization rules
