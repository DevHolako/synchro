# 02: In-App Notification Bell and Automated Schedule Emails

**What to build:** User notification center featuring a top-bar bell with unread badge counter for timetable notices and announcements, coupled with queued transactional emails on timetable/exam publication.

**Blocked by:** Part 01 - 04: User Roles and Expiring Invitation Token Provisioning

**Status:** completed

- [x] Laravel Database notifications schema (`notifications` table)
- [x] Top-bar notification bell component in React header with real-time unread badge counter
- [x] Notification dropdown displaying recent announcements with "Mark as Read" action
- [x] Queued Mailable classes for Timetable Publication and Official Convocation Release
- [x] Asynchronous queue dispatch ensuring request response times remain < 200ms
- [x] Automated tests asserting notification generation, unread counter updates, and mail queueing

## Design decisions (2026-10-02, Ticket 02)

- **Database Notifications Schema**:
  - Generated `notifications` table migration (`2026_10_18_090000_create_notifications_table.php`).
- **Queued Transactional Notifications**:
  - `TimetablePublishedNotification` dispatched on queue `notifications` with database and mail channels for student groups.
  - `ExamConvocationPublishedNotification` dispatched on queue `notifications` with database and mail channels for exam candidates when an exam is published via `QueueExamDocumentsAction`.
- **Top-Bar Notification Bell & Dropdown**:
  - `NotificationBell` in `resources/js/components/notification-bell.tsx` integrated into `AppSidebarHeader` and `AppHeader`.
  - Displays real-time unread badge counter (shared Inertia prop `unreadNotificationsCount`), dropdown list of recent notifications with relative timestamps, unread indicator dots, and "Marquer tout comme lu" action.
  - Clicking a notification marks it as read via `PATCH /notifications/{id}/read` and navigates to its attached link.
- **Single-Action Backend Architecture (ADR 0009)**:
  - `ListRecentNotificationsAction`, `MarkNotificationAsReadAction`, `MarkAllNotificationsAsReadAction`, and `NotifyTimetablePublishedAction`.
  - HTTP endpoints: `GET /notifications`, `PATCH /notifications/{id}/read`, `POST /notifications/read-all`.
- **Localization**:
  - 100% key parity maintained across `types.ts`, `fr.ts`, and `en.ts` for all notification bell UI strings, and `lang/fr/messages.php` and `lang/en/messages.php` for notification titles, bodies, and emails.
