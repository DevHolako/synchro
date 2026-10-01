# Synchro

Timetable scheduling and examination logistics platform for ISGA, catering to both executive/weekend programs ("Temps Aménagé") and standard daytime programs ("Formation Initiale").

## Language

### Core Structure

**Campus**:
A physical site of the institution (e.g., Casablanca, Rabat) grouping buildings, rooms, and localized academic coordination.
_Avoid_: School, branch, location

**Program Modality**:
The schedule regime of an academic program, distinguishing "Temps Aménagé" (executive evening/weekend slots) from "Formation Initiale" (standard weekday daytime slots).
_Avoid_: Track type, schedule type

**Course Session**:
A scheduled teaching event for a student group, module, and teacher in an assigned room within a date and time slot.
_Avoid_: Slot, class, period

### Examination Logistics

**Exam**:
A timed assessment event evaluating a specific module for an enrolled student group.
_Avoid_: Test, quiz

**Exam Period**:
A structured institutional timeframe grouping examinations by session type ("Session Normale" or "Rattrapage").
_Avoid_: Exam week, semester finals

**Exam State**:
The formal progression state of an examination (`Draft`, `Scheduled`, `Published`, `Completed`, `Archived`).
_Avoid_: Status, stage

**Exam Capacity**:
The reduced, distanced seating threshold of a room strictly enforced during examinations.
_Avoid_: Room capacity, size, normal capacity

**Room Split**:
The automated partitioning of an exam across multiple rooms when student headcount exceeds the primary room's exam capacity.
_Avoid_: Multi-room, room divide

**Force Single Room**:
An explicit administrator override that packs all exam candidates into one room, intentionally bypassing the exam capacity room split.
_Avoid_: Override split, squeeze

**Invigilator**:
A teacher or academic staff member assigned to oversee room conduct and student attendance during an exam.
_Avoid_: Proctor, supervisor, watcher

**Convocation**:
The official personalized document containing an exam schedule and unique verification QR code issued to an enrolled student.
_Avoid_: Ticket, exam pass

**Digital Check-in**:
The interactive real-time attendance marking and room allocation verification triggered by scanning a student's convocation QR code.
_Avoid_: Roll call, paper check

**Emergency Reschedule**:
An administrative modification to a `Published` exam or session that invalidates previous convocations, reissues them, and dispatches urgent multi-channel notifications.
_Avoid_: Edit, change

### Attendance & Availability

**Session Attendance Register**:
A per-course-session roster allowing instructors or coordinators to record attendance (Present, Absent, Late, Excused) with one-click toggles.
_Avoid_: Roll sheet, class register

**Teacher Unavailability**:
A declared constraint (recurring weekly pattern or ad-hoc specific date/time) flagging when a teacher cannot teach, triggering a soft conflict warning upon scheduling.
_Avoid_: Time off, leave request

### Evaluation & Grading

**Continuous Assessment**:
Ongoing coursework evaluations (quizzes, practical work, projects) weighted alongside the final exam score to produce a module grade out of 20.
_Avoid_: Coursework, homework, CC

**Official Grade Record (PV)**:
The final deliberated grade sheet locked by administration and archived as a signed PDF document.
_Avoid_: Report card, transcript

### Integrations & Feeds

**Calendar Feed (iCal/Webcal)**:
A private, token-secured `.ics` calendar subscription URL that dynamically synchronizes course and exam schedules into external calendar apps (Google, Apple, Outlook).
_Avoid_: Calendar export, static download

### User Management & Provisioning

**Permission**:
The atomic capability granting access to a specific action or resource (e.g., `create:rooms`, `manage:referentials`). Permissions are the sole gate of check across all authorization boundaries.
_Avoid_: Privilege, claim

**Role**:
A structured institutional grouping/bundle of permissions assigned to users (`Administrator`, `Coordinator`, `Teacher`, `Student`). Roles are never checked directly as gates; they serve strictly to bundle permissions.
_Avoid_: Rank, clearance, user type

**Invitation Token**:
A secure, single-use signed link, valid for 72 hours, emailed when an account is provisioned (individually or by Spreadsheet Import) so the user can set their initial password.
_Avoid_: Reset token, signup code

**Spreadsheet Import**:
An uploaded CSV/XLSX file of Rooms, Modules, Teachers, or Students processed in the background on the `imports` queue. It is all-or-nothing: every row is validated first, and if any row is invalid nothing is saved and the problems are reported with their exact row numbers. Its status moves from `pending` to `processing` to `succeeded` or `failed`.
_Avoid_: Upload, batch, sync

### Conflict Engine

**Hard Conflict**:
A strictly forbidden physical resource collision (same teacher, same room, or same student group double-booked) resulting in a non-bypassable rejection.
_Avoid_: Error, blocking issue

**Soft Conflict**:
A policy violation (such as room capacity overage or teacher-declared unavailability) that allows an administrator override recorded with an audit trail.
_Avoid_: Warning, soft error

### Localization & Multi-Language Standards

**Full-Stack Localization (EN Base, FR Default Display)**:
The application codebase and schemas are structured with English (`en`) as the base, while the user interface MUST display French (`fr`) by default. 100% full translation coverage is mandated across both frontend and backend. Zero user-facing strings (labels, descriptions, placeholders, table headers, buttons, badges, toasts, modal titles) may be hardcoded in JSX or PHP.
_Avoid_: Hardcoded French strings, untranslated English strings, partial localization

