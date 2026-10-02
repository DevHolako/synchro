# 05: Emergency Reschedule Workflow with Convocation Invalidation

**What to build:** Administrative Emergency Reschedule action for modifying a `Published` exam. Requires explicit confirmation, invalidates previously issued QR codes, regenerates updated Convocations, and queues emergency notification alerts.

**Blocked by:** 04: Interactive Mobile QR Scan Exam Check-in Interface

**Status:** done

- [x] Emergency Reschedule modal triggered when editing a `Published` exam's date, time, or room
- [x] Single-Action `EmergencyRescheduleExamAction` updating schedule attributes and incrementing revision number
- [x] Automatic revocation of previous convocation UUIDs; scanned revoked tokens display "SUPERSEDED" banner
- [x] Generation of updated Convocation PDFs with new QR codes
- [x] Automated queuing of urgent SMS/WhatsApp and email alerts to all affected students and invigilators
- [x] Automated tests asserting token revocation, superseded error handling on scan, and notification job dispatch

## Design decisions (2026-10-02, defaults confirmed with "go")

- **Trigger**: "Report d'urgence" on published exams that have not started, for exam managers. A dialog takes the new date and times, optionally new rooms (ordered), a required reason (10–1000 characters) and an explicit confirmation that current convocations will be invalidated (`confirmed` must be accepted).
- **Action** (`EmergencyRescheduleExamAction`, one transaction):
  - checks the period and the grid, and refuses room and group clashes at the new time;
  - releases invigilators busy at the new time and names them, to be replaced in the sheet;
  - new rooms mean a new split (and Force Single Room is dropped); otherwise seats stay;
  - check-ins are cleared;
  - `exams.revision` goes up by one, and an immutable `exam_reschedules` row records who, when, why, the old and new times and rooms, and the released invigilators.
- **Convocations**: every issued UUID goes to `superseded_convocations` (with its revision) and each candidate gets a new one. After commit, the old PDFs are deleted and new convocations and room sheets are queued as in ticket 03. Scanning an old QR code opens `convocations/superseded`: "Convocation périmée", the new time and revision, and the student's current room and seat.
- **Alerts** (after commit):
  - a queued `ExamRescheduledNotification` (mail, `notifications` queue) to every candidate (new room and seat), invigilator (their room) and released invigilator;
  - an urgent message to the phone on their profile through `UrgentMessageGateway` (ADR 0003), sent by `SendUrgentMessageJob` on the `notifications` queue;
  - the only driver is `log` (`URGENT_MESSAGES_DRIVER`); SMS and WhatsApp arrive in Part 06.
- **Everywhere else**: lists and the check-in screen read the new time; the iCal SEQUENCE grows with the exam's `updated_at`. Rows show "Rév. n" with the latest reason as a tooltip.

