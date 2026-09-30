# 02: In-App Notification Bell and Automated Schedule Emails

**What to build:** User notification center featuring a top-bar bell with unread badge counter for timetable notices and announcements, coupled with queued transactional emails on timetable/exam publication.

**Blocked by:** Part 01 - 04: User Roles and Expiring Invitation Token Provisioning

**Status:** ready-for-agent

- [ ] Laravel Database notifications schema (`notifications` table)
- [ ] Top-bar notification bell component in React header with real-time unread badge counter
- [ ] Notification dropdown displaying recent announcements with "Mark as Read" action
- [ ] Queued Mailable classes for Timetable Publication and Official Convocation Release
- [ ] Asynchronous queue dispatch ensuring request response times remain < 200ms
- [ ] Automated tests asserting notification generation, unread counter updates, and mail queueing
