# Spec 04: Examination Logistics & Convocations

## Problem Statement

Exam organization in higher education is high-risk. Overcrowded rooms violate distancing regulations, invigilators get double-booked, generating paper convocations and room lists manually takes days, and if an exam hall changes at the last minute, students show up in the wrong room while security cannot verify genuine student tickets.

## Solution

An industrial-grade Examination Logistics module managing the complete 5-state exam lifecycle (`Draft` ➔ `Scheduled` ➔ `Published` ➔ `Completed` ➔ `Archived`). It provides automatic alphabetical room splitting when group headcount exceeds room `exam_capacity`, an administrative `Force Single Room` override, strict anti-collision invigilator assignments (1 lead per room + assistant if > 25 students), automated PDF generation of official Convocations featuring unique QR codes, attendance sheets, and an interactive smartphone digital check-in interface that verifies room assignments and records attendance in real time.

## User Stories

1. As a Coordinator, I want to create Exam Periods (Normal Session and Retake Session) and schedule Exams for specific modules and groups.
2. As a Coordinator, I want the system to enforce the 5-state lifecycle (`Draft`, `Scheduled`, `Published`, `Completed`, `Archived`), so that unapproved exam plans are never visible to students.
3. As a Coordinator, I want the system to automatically split an exam across 2 or more rooms if the group size exceeds the room's Exam Capacity, dividing students alphabetically (e.g. A–L in Room 1, M–Z in Room 2), so that distancing regulations are guaranteed.
4. As an Administrator, I want a `Force Single Room` option when scheduling an exam, so that I can intentionally override room capacity splitting when an amphitheatre allows it, with a recorded audit justification.
5. As a Coordinator, I want to assign Invigilators to each allocated exam room with strict validation preventing double-booking, ensuring at least 1 lead invigilator per room.
6. As a Coordinator, I want the system to recommend an assistant invigilator whenever an allocated room's headcount exceeds 25 students, so that exam supervision remains thorough.
7. As a Student, I want to download my official Convocation PDF once an exam is `Published`, featuring my personal details, assigned room, seat/split reference, and a secure QR code.
8. As an Invigilator, I want to scan a student's convocation QR code at the exam door using my phone or tablet, so that an authenticated verification screen instantly confirms their identity and warns if they are in the wrong room.
9. As an Invigilator, I want to tap "Mark Present" directly on the QR scan verification screen, so that student attendance is recorded in the database in real time without paper rosters.
10. As a Coordinator, I want to download official Examination Attendance Rosters (Feuille d'émargement) and door-posting lists as PDFs, so that paper backups are available.
11. As a Coordinator, I want an Emergency Reschedule workflow if a `Published` exam must be relocated or postponed, which requires my explicit confirmation, invalidates previously issued QR codes, regenerates updated Convocations, and triggers urgent notification alerts.

## Implementation Decisions

- **Five-State Lifecycle (ADR 0005)**: Enforced via model state transitions or enum methods: `Draft`, `Scheduled`, `Published`, `Completed`, `Archived`.
- **Automatic Room Splitting Algorithm**:
  - Given student list sorted by `last_name, first_name` and rooms `[R1 (cap C1), R2 (cap C2)]`.
  - Slice student array proportionally or by threshold (e.g. first C1 students to R1, remaining to R2).
  - Persisted in `exam_room_assignments` with `allocated_students_count` and foreign key relationships to specific student profiles.
- **Force Single Room Override (ADR 0002)**: When enabled, assigns entire student group to primary room regardless of `exam_capacity`, flagging a soft conflict and recording audit entry.
- **Invigilator Staffing Rules**: `exam_invigilators` entity recording `exam_room_assignment_id`, `teacher_id`, and `role` (`principal`, `adjoint`).
- **Cryptographic QR Code & Verification (ADR 0008)**:
  - Each convocation embeds a QR code pointing to `/verify/convocation/{uuid}` signed with HMAC SHA-256.
  - If the exam was subjected to an Emergency Reschedule, previously issued UUIDs return a clear visual warning: "CONVOCATION SUPERSEDED — Please present updated document for Room X".
  - Authenticated invigilators see student photo, matricule, assigned room, and a reactive "Check In" button.
- **PDF Generation Engine**: Laravel DomPDF or Browsershot compiling clean, print-ready vector layouts with official ISGA header, candidate details, room allocation, and SVG QR code.

## Testing Decisions

- **Seam**: End-to-end integration tests on `ScheduleExamAction`, `SplitExamRoomsAction`, `VerifyConvocationAction`, and `EmergencyRescheduleExamAction`.
- **Coverage**:
  - Alphabetical room split logic with edge cases (odd student numbers, exactly matching capacity, capacity requiring 3 rooms).
  - `Force Single Room` override behavior asserting single room assignment and audit record creation.
  - Invigilator collision detection blocking overlapping exam assignments.
  - QR verification endpoint asserting valid check-in, invalid/tampered token rejection, and superseded status on emergency rescheduled exams.
  - PDF generation asserting output binary validity and QR code inclusion.

## Out of Scope

- Biometric fingerprint or facial recognition scanner hardware integration.
- Automated multiple-choice optical paper bubble-sheet scanning (OMR).

## Further Notes

- The interactive check-in route must be responsive and optimized for mobile screens so invigilators can scan comfortably using native smartphone camera apps or integrated barcode scanners.

## Part-wide decisions (2026-10-02, design round before ticket 01)

- **Working mode**: a full design round for tickets 01 and 02; for tickets 03–05, defaults are posted and confirmed with "go".
- **Names for the alphabetical split (ticket 02)**: `student_profiles` gets `last_name` and `first_name`, filled when a student is provisioned or imported (the student import gets both columns instead of `name`). `users.name` stays the display name, so a student editing it cannot change their place in the official order.
- **Student photo (ticket 04)**: deferred. The check-in screen shows full name, matricule, group, assigned room and an initials avatar; the invigilator checks the student card. Photos (personal data, law 09-08) would be their own ticket.
- **Exams in the conflict engine**: exams are an `OccupancySource` (morph alias `exam`); only non-draft exams book resources. "Force Single Room" becomes a soft `ConflictType` (ticket 02), and the exam capacity rule uses `exam_capacity`.
- **PDF engine (ticket 03)**: `barryvdh/laravel-dompdf` and `bacon/bacon-qr-code` (SVG), generated in thin queued jobs; the deferred weekly-timetable PDF of Part 03 can reuse the pipeline.
- **Emergency-reschedule alerts (ticket 05)**: a queued `ExamRescheduledNotification` by mail on the `notifications` queue, dispatched after commit, plus a minimal `UrgentMessageGateway` with only a `log` driver (ADR 0003); SMS and WhatsApp drivers come with Part 06.
- **Exams in the iCal feed**: published exams from ticket 01; ticket 02 adds the room and invigilated exams; ticket 05 raises SEQUENCE on a reschedule.
- **Live check-in (ticket 04)**: `usePoll` every 10 s on the room roster; Reverb stays deferred.

## Review alignment (2026-10-02, Part 04 review)

- **Exam capacity**: enforced by the room split itself. Rooms that seat fewer than the candidates are refused, and Force Single Room is the audited override. There is no separate `CapacityRule` for exams in the conflict engine (it stays specific to course sessions).
- **Official names**: convocations, room sheets and check-in screens print the official "SURNAME Given name" from `student_profiles` (`User::officialName()`). The editable display name stands in only when no surname was recorded.
