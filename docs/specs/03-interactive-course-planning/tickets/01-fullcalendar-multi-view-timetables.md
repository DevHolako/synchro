# 01: React FullCalendar Timetable Views with Open Operational Grid

**What to build:** Interactive timetable viewer powered by React FullCalendar with an open operational grid (08:00 to 22:00, 7 days/week). Schedulers and students can inspect timetables across four perspectives: by Group, by Teacher, by Room, and Global Campus View, with real-time syllabus hours tracking.

**Blocked by:** Part 01 - 03: Module Catalog with Syllabus Hours and Color Coding

**Status:** done

- [x] FullCalendar React component integrated into Inertia.js SPA with day, week, month and list views (resource views replaced, see below)
- [x] Operational grid spanning 08:00 to 22:00 without arbitrary modality restrictions
- [x] Multi-perspective filters: Group, Teacher, Room, and Global Campus timetable
- [x] Course session cards styled with module-specific color codes and session badges
- [x] Syllabus progress widget showing planned hours vs total syllabus hours for the active module
- [x] Automated tests asserting endpoint query performance and eager loading of relations

## Design decisions (agreed 2026-10-01)

- **Library**: FullCalendar 6.1 (`core`, `react`, `daygrid`, `timegrid`, `list`; all MIT). `interaction` arrives with ticket 03. Version 7 was skipped for now: new packaging and theming, a Temporal polyfill, little field history.
- **Deviation: no resource views.** FullCalendar's resource views (one column per room) need the paid Premium licence. Group, Teacher and Room are filters on the standard week grid; the Global Campus view is a list (`listWeek`/`listDay`) of every session on the campus, sorted by time, with room, groups and teacher on each row. A custom "rooms × hours" board, or the Premium licence, can come later if coordinators ask for it.
- **Who sees what**: the new `BrowseSchedules` (`browse:schedules`) permission (Coordinator; Administrator holds all) unlocks the four perspectives. Everyone else with `ViewSchedules` gets "My timetable" only: a student's group, otherwise the sessions the user teaches. It is resolved from data (the student profile), never from the role. Any other perspective in the URL without the permission is a 403.
- **Data flow**: an Inertia page, `GET /timetable?perspective=&id=&date=&view=` (`timetable.index`); the URL holds the state, so links and the back button work. Moving between periods is a partial reload of `sessions` and `filters`. Reads go through `ListTimetableSessionsAction` (eager loads module, teacher, room and building, groups and override records; the query count does not grow with the sessions), for reuse by the iCal feed and the API.
- **Range**: the server derives the dates from the view and date (day, Monday-start week, or the six-week month grid starting on the Monday before the 1st), so the client cannot ask for an arbitrary range (at most 42 days). Sessions never cross midnight, so `starts_at` alone places them.
- **Times**: sessions are sent as offset-less wall-clock times (`2026-10-03T08:30:00`) and the calendar runs with `timeZone: 'UTC'`, so the browser never shifts them (Morocco's Ramadan clock changes included). The school's wall clock (shared prop `scheduleTimezone`, the server's `SchoolClock`) is fed to `now`, so "today", the now-indicator and the locks match the server whatever the browser's zone (updated by the Part 03 review). Ticket 03 formats dropped dates back to `Y-m-d H:i`, so the write requests stay unchanged. `config/app.php` stays on `UTC`; the iCal `TZID` is decided with ticket 05.
- **Syllabus progress**: per module and group. Every session of the module that includes the group counts, past or future, and a session shared by several groups counts in full for each. Shown next to the Group perspective (and a student's own timetable): all active modules of the group's program, plus inactive ones that already have sessions. Display: `22,5 / 30 h` in the UI language's number format, bar capped at 100%, red with `+3 h` when over-planned. Clicking a module highlights its sessions. Only `total_hours` is compared: sessions are not typed lecture/TP.
- **Cards and details**: background in the module colour with automatic text contrast; module code, time, then room, groups and teacher badges, minus the one the timetable is about. A warning icon marks sessions saved over a soft conflict. Clicking opens a read-only details dialog (module, time, teacher, room and building, groups, override justifications); tickets 03 and 04 add editing and attendance there.
- **Grid and defaults**: 08:00–22:00, 7 days, weeks start on Monday, FullCalendar's `fr`/`en` locale follows the UI language. No modality-based scrolling ("group preference" is not modelled). Without a view in the URL: `listWeek` on phones (< 768px) and for a campus, `timeGridWeek` otherwise. Browsers land on the first active campus by name for the current week.
- **What is listed**: the calendar shows every session in the range, including past ones and those of since-deactivated modules, rooms or groups. Filter options list active groups, rooms and campuses (plus the selected one even when inactive) and every teacher.
- **Empty states**: a student without a group gets a "not assigned to a group" message; a Group, Teacher or Room perspective with nothing selected asks the user to pick one. Neither is an error.
