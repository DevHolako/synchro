# 04: One-Click Course Session Attendance Register

**What to build:** Streamlined attendance screen for instructors and coordinators to record student attendance for a course session (Present, Absent, Late, Excused) with a single "Mark All Present" toggle taking under 30 seconds.

**Blocked by:** 01: React FullCalendar Timetable Views with Open Operational Grid

**Status:** ready-for-agent

- [ ] SessionAttendance model linking `course_session_id`, `student_id`, `status`, and `remarks`
- [ ] Course session attendance drawer/modal displaying enrolled student roster
- [ ] "Mark All Present" one-click button with individual click-to-toggle status pills
- [ ] Attendance summary metrics (total present, absent percentage) computed per student and module
- [ ] Automated tests asserting attendance submission, bulk toggle, and student absence calculations
