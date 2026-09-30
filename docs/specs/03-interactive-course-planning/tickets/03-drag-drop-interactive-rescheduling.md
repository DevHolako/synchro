# 03: Interactive Drag-and-Drop Rescheduling with Conflict Feedback

**What to build:** Drag-and-drop and resize capabilities on FullCalendar. When dropped, the session calls the conflict engine. Hard conflicts instantly snap the event back to its original slot with an error toast; soft conflicts open a confirmation modal requiring an audit justification.

**Blocked by:** 01: React FullCalendar Timetable Views with Open Operational Grid, Part 02 - 03: Soft Conflict Detection and Audit Trail Override

**Status:** ready-for-agent

- [ ] FullCalendar event drag and resize handlers connected to Inertia / API endpoints
- [ ] Asynchronous conflict verification upon drop
- [ ] Optimistic snapback handling reverting DOM position if a Hard Conflict (HTTP 422) is returned
- [ ] Soft conflict confirmation modal presenting warning details and an audit comment input
- [ ] Persistent update and success toast upon validated drop or authorized override
- [ ] Automated tests asserting move/resize controller endpoints and snapback payload structures
