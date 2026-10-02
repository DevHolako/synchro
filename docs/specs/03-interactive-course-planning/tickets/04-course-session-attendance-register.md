# 04: One-Click Course Session Attendance Register

**What to build:** Streamlined attendance screen for instructors and coordinators to record student attendance for a course session (Present, Absent, Late, Excused) with a single "Mark All Present" toggle taking under 30 seconds.

**Blocked by:** 01: React FullCalendar Timetable Views with Open Operational Grid

**Status:** done

- [x] SessionAttendance model linking `course_session_id`, `student_id`, `status`, and `remarks`
- [x] Course session attendance drawer/modal displaying enrolled student roster
- [x] "Mark All Present" one-click button with individual click-to-toggle status pills
- [x] Attendance summary metrics (total present, absent percentage) computed per student and module
- [x] Automated tests asserting attendance submission, bulk toggle, and student absence calculations

## Design decisions (2026-10-02, recommended defaults accepted without a discussion round)

- **Model**: `session_attendances`: one row per session and student (unique), `status` (`present`, `absent`, `late`, `excused`; `AttendanceStatus`), optional `remarks` (255), `recorded_by`. Foreign keys restrict deletion: started sessions cannot be deleted anyway, and students or recorders with marks join `User::hasSchedulingHistory()`.
- **Who**: new `RecordAttendance` (`record:attendance`) for Teacher and Coordinator (Administrator holds all). A teacher takes the registers of sessions they teach; holders of `ManageSchedules` take any (`CourseSessionPolicy::recordAttendance`).
- **When**: once the session has started, by the school's clock; it stays editable afterwards for corrections. No lock yet.
- **Roster**: the students of the session's groups, plus anyone already marked who has since changed group.
- **Saving**: `PUT /course-sessions/{session}/attendance` (204) saves the rows changed since the sheet loaded, so a concurrent save never overwrites untouched rows; a row sent with a `null` status removes that mark and its remark. Unmarked students are not stored. `GET` returns the register (JSON, read with `useHttp`). A remark needs a status. (Updated by the Part 03 review.)
- **Metrics**: counts per status for the session; per student, `module_absence_rate`: absences in this module over recorded sessions, computed on the server. Late arrivals and excused absences are not counted as absences.
- **UI**: "Feuille de présence" in the session details dialog (started sessions, allowed users) opens a side sheet: one row per student with status pills and a remark, "Tous présents", counts, save. Timetable polling pauses while it is open.
- **Out of scope**: students viewing their own attendance, locking registers, absence alerts.
