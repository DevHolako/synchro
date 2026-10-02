# 01: Exam Period Definition and 5-State Exam Scheduling

**What to build:** Comprehensive examination planning module. Coordinators define Exam Periods (Normal Session and Retake Session) and schedule exams across the 5-state lifecycle (`Draft`, `Scheduled`, `Published`, `Completed`, `Archived`), keeping provisional exams hidden from students until published.

**Blocked by:** Part 01 - 03: Module Catalog with Syllabus Hours and Color Coding, Part 02 - 02: Core Conflict Detector Service

**Status:** done

- [x] ExamPeriod model storing `name`, `session_type` (`normal`, `rattrapage`), `start_date`, and `end_date`
- [x] Exam model linking `exam_period_id`, `module_id`, `group_id`, `date`, `start_time`, `end_time`, and `state` (`draft`, `scheduled`, `published`, `completed`, `archived`)
- [x] Explicit state machine transitions with validation guards
- [x] Exam calendar and listing view for coordinators to manage exam timetables
- [x] Student access control restricting visibility strictly to `Published`, `Completed`, and `Archived` exams
- [x] Automated tests asserting lifecycle transitions and student authorization rules

## Design decisions (2026-10-02, discussion round; all recommendations accepted)

- **Model**:
  - `exam_periods`: `name`, `session_type` (`ExamSessionType`), `academic_year` (added; matches groups), `start_date`, `end_date`.
  - `exams`: `exam_period_id`, `module_id`, `starts_at`/`ends_at` (instead of `date` + times, like course sessions), `state` (`ExamState`), `published_at`, `published_by` (added).
  - Several groups per exam through `exam_student_group` (instead of `group_id`); every group must belong to the module's program.
  - Foreign keys restrict deletion: a module, group or period with exams cannot be deleted, and `published_by` joins `User::hasSchedulingHistory()`.
- **Times**: one day, 08:00–22:00 on quarter hours (ADR 0004), inside the period's dates, and never already started by the school's clock (create, move, schedule, publish).
- **Uniqueness**: a group sits each module's exam at most once per period.
- **Lifecycle** (`ExamState::canTransitionTo()`, `ChangeExamStateAction`: a conditional update on the state the exam was read in):

  | Move | Rule |
  |---|---|
  | `Draft` → `Scheduled` | No hard conflict (`ScheduleExamAction`). |
  | `Scheduled` → `Draft` | Releases what it booked. |
  | `Scheduled` → `Published` | Per exam, or every upcoming scheduled exam of a period at once. Sets `published_at`/`published_by`. |
  | `Published` → `Completed` | Automatic: `exams:complete-ended` every 15 minutes once the exam has ended. |
  | `Completed` → `Archived` | By archiving the period, once all its exams are completed. |

  Draft and scheduled exams can be edited and deleted; from publication on, an exam is locked (ticket 05 adds the emergency reschedule). There is no cancelled state.
- **Conflicts**:
  - Drafts book nothing: a draft is saved even when it clashes, and the form shows the clashes as warnings (`POST /exams/check`).
  - Scheduled and later exams book their groups. `ExamOccupancy` joins the conflict engine, so exams and course sessions block each other for the same group.
  - `SessionSlot` now holds lists of teachers, rooms and groups plus its booking type; `Conflict` names the colliding booking (`booking_type`, `booking_id`, replacing `session_id`). Rooms and invigilators join in ticket 02.
- **Periods**: their status (upcoming, ongoing, ended, archived) is derived from dates and exams, never stored. A period cannot be re-dated so that its exams fall outside, and can only be deleted while empty. Periods may overlap.
- **Visibility** (`Exam::scopeVisibleTo`): `ManageExams` sees every state; everyone else with `ViewExams` sees published, completed and archived exams of their group (students) or of the modules they teach (teachers). Ticket 02 adds invigilated exams.
- **iCal**: published exams concerning the user join their feed (`exam-{id}` UIDs, no location until ticket 02).
- **UI**: `/exams` (sidebar "Examens", `ViewExams`/`ManageExams`). It offers a period switcher, stats by state, filters, a table and a read-only FullCalendar view loaded on demand, plus the period and exam dialogs and confirmations for every move. Students and teachers get the same page read-only ("Mes examens").
- **Deferred**:
  - Retake candidates are the whole group until Part 05 grades.
  - The `/api/v1` controllers (Part 06).
  - Exams on the course timetable.
- **Added in the round, recorded after review**:
  - Exams still draft or scheduled once their start has passed show an "overdue" badge (round 2, Q17).
  - Requests cap an exam at 20 rooms and a room at 10 assistants, as sanity limits.
