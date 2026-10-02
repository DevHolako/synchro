# 05: Tokenized Personal iCal Calendar Subscription Feeds

**What to build:** Private, cryptographically signed `.ics` calendar subscription endpoints (`webcal://...`) issued to each student and teacher, dynamically synchronizing courses and exams into external calendar apps (Google Calendar, Apple Calendar, Outlook).

**Blocked by:** 01: React FullCalendar Timetable Views with Open Operational Grid

**Status:** done

- [x] Signed private token generation per user stored on profile (on `users`, see below)
- [x] Route `/feeds/calendar/{token}.ics` serving standard RFC 5545 VCALENDAR stream
- [x] Dynamic inclusion of course sessions, rooms, teachers, and published exams (exams arrive with Part 04)
- [x] Automatic reflecting of updated time/room when sessions are rescheduled
- [x] Automated tests asserting iCal feed MIME type, timezone compliance, and token revocation

## Design decisions (2026-10-02, recommended defaults accepted without a discussion round)

- **Token**: one per user, 48 random characters, on `users`: `calendar_feed_token_hash` (SHA-256, unique, the lookup) and `calendar_feed_token` (encrypted, only so the owner can copy the link again). Creating a new one revokes the old; it can also be turned off. Inactive accounts get 404.
- **Route**: `GET /feeds/calendar/{token}.ics`, public (the token is the credential), throttled to 60 requests a minute, 404 for unknown or revoked tokens. `text/calendar; charset=utf-8`.
- **Contents**: the user's own timetable (`TimetableScope::mine`: a student's group, or the sessions a user teaches), 90 days back to a year ahead. Exams join with Part 04.
- **Time zone**: sessions are stored as the school's wall-clock time; the feed converts them to UTC (`DTSTART:…Z`) with the new `app.schedule_timezone` (`SCHEDULE_TIMEZONE`, default `Africa/Casablanca`), so no VTIMEZONE is needed and Morocco's rule changes come from the time-zone database. Note: tzdata 2026c has Morocco back on UTC+0 from 2026-09-20; keep the image's tzdata current.
- **Changes**: a session keeps its UID (`course-session-{id}@host`) when moved, with a growing SEQUENCE and LAST-MODIFIED; deleted sessions drop out of the feed. No cache: the feed is one indexed query.
- **Format**: a small native RFC 5545 writer (`App\Services\Calendar\ICalendarWriter`: CRLF lines, TEXT escaping, folding at 75 octets without splitting UTF-8), no new dependency.
- **UI**: "Synchroniser mon agenda" on the timetable opens a dialog with the webcal link (Apple, Outlook), the https link (Google "From URL"), copy buttons, "open in my calendar app", regenerate and turn off.
