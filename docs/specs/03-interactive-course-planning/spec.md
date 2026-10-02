# Spec 03: Interactive Course Planning

## Problem Statement

Constructing and maintaining timetables for executive programs ("Temps Aménagé") and regular tracks using static tools is tedious. Schedulers lack visual indicators for remaining syllabus hours, cannot easily move sessions around, struggle to track whether professors actually delivered planned hours, and students lack direct synchronization with their mobile calendar apps.

## Solution

An interactive, responsive schedule management module powered by React FullCalendar and Inertia.js. It features a batch generation wizard to schedule intensive modular blocks, fluid drag-and-drop course session placement with real-time conflict feedback, real-time syllabus hour depletion tracking, a one-click session attendance register, and private tokenized iCal (`.ics`) subscription feeds for seamless calendar integration across external devices.

## User Stories

1. As a Coordinator, I want to view course timetables across multiple perspectives (by Group, by Teacher, by Room, and Global Campus View), so that I can evaluate scheduling from all angles.
2. As a Coordinator, I want the calendar to provide an open operational grid (08:00 to 22:00, 7 days a week) without arbitrary lockout walls, so that I have complete scheduling flexibility.
3. As a Coordinator, I want to use a Batch Scheduling Wizard to generate multiple weekend sessions for a module in one step, so that I do not have to create each 3-hour session manually.
4. As a Coordinator, I want to drag and drop or resize sessions on the calendar, so that schedule adjustments are intuitive.
5. As a Coordinator, I want dragged sessions that cause a Hard Conflict to snap back to their original position with an explicit error toast, so that invalid states are never saved.
6. As a Coordinator, I want dragged sessions that cause a Soft Conflict to open a confirmation modal showing the exact warning and an audit note field, so that I can confirm with justification or cancel.
7. As a Coordinator, I want a sidebar widget displaying planned vs total syllabus hours for the active module, so that I never under-schedule or over-schedule a course.
8. As a Teacher, I want to view my personalized schedule in the web application and on mobile, so that I know exactly when and where I teach.
9. As a Teacher or Coordinator, I want to open a Session Attendance Register for any completed or active session to mark student attendance (Present, Absent, Late, Excused) with a single "Mark All Present" toggle, so that attendance tracking takes under 30 seconds.
10. As a Student or Teacher, I want a private, secure iCal subscription link (`webcal://...`) that I can add to Google Calendar, Apple Calendar, or Outlook, so that my personal smartphone calendar updates automatically whenever sessions change.
11. As a Student, I want to filter my group's timetable and download an official PDF copy of the weekly schedule, so that I have an offline reference.

## Implementation Decisions

- **Calendar Framework**: FullCalendar React integrated seamlessly into the Inertia.js SPA, rendering events with module-specific color codes.
- **Flexible Grid (ADR 0004)**: Unrestricted 08:00–22:00 operational grid, 7 days/week, dynamically scrolling to relevant hours based on group preference.
- **Batch Module Scheduler**: Single-Action `BatchCreateCourseSessionsAction` accepting date patterns, time ranges, group, module, teacher, and room, executing conflict checks across all target slots in a single atomic transaction.
- **Optimistic Drag UX (ADR 0002)**: Dropping an event triggers an asynchronous check. Hard conflicts revert the event and display toast notifications; soft conflicts display a confirmation modal before persisting.
- **Session Attendance Register**: Entity `session_attendances` storing `course_session_id`, `student_id`, `status` (`present`, `absent`, `late`, `excused`), `remarks`, editable via a fast grid UI.
- **Tokenized iCal Feed (ADR 0010)**: Route `/feeds/calendar/{token}.ics` serving dynamic VCALENDAR responses generated via `spatie/icalendar-generator` or native builder, secured via user-specific signed tokens with automatic cache invalidation on schedule mutation.

## Testing Decisions

- **Seam**: Full integration tests on `CourseSessionController` and `BatchCreateCourseSessionsAction`.
- **Coverage**:
  - Batch generation verifying atomic creation of multiple sessions and rollback if a hard conflict occurs on any single date.
  - Drag-and-drop endpoint verifying snapback payload on 422, modal trigger payload on 409, and successful 200 persistence.
  - Course attendance toggle asserting state persistence and attendance percentage calculations.
  - iCal feed HTTP response asserting valid RFC 5545 format, correct timezone handling, and cancellation/reschedule reflections.

## Out of Scope

- Video conference meeting room auto-generation (Zoom / MS Teams link generation).
- Classroom hardware telemetry (smart board power state or smart projector controls).

## Further Notes

- Eager load `group`, `module`, `teacher.user`, and `room` in all calendar endpoints to prevent N+1 query performance degradation.
- **Deferred (agreed 2026-10-02, Part 03 review):** the official PDF copy of the weekly timetable (user story 11) is not built in Part 03. PDF generation must run as a queued job (ADR 0012), and Part 04 builds that pipeline for convocations; the timetable PDF reuses it there or later.
