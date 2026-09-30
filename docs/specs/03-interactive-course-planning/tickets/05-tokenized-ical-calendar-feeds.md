# 05: Tokenized Personal iCal Calendar Subscription Feeds

**What to build:** Private, cryptographically signed `.ics` calendar subscription endpoints (`webcal://...`) issued to each student and teacher, dynamically synchronizing courses and exams into external calendar apps (Google Calendar, Apple Calendar, Outlook).

**Blocked by:** 01: React FullCalendar Timetable Views with Open Operational Grid

**Status:** ready-for-agent

- [ ] Signed private token generation per user stored on profile
- [ ] Route `/feeds/calendar/{token}.ics` serving standard RFC 5545 VCALENDAR stream
- [ ] Dynamic inclusion of course sessions, rooms, teachers, and published exams
- [ ] Automatic reflecting of updated time/room when sessions are rescheduled
- [ ] Automated tests asserting iCal feed MIME type, timezone compliance, and token revocation
