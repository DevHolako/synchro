# 04: Mobile API Endpoints for Exam Convocations and QR Check-in

**What to build:** Specialized mobile REST API routes allowing future native mobile apps to retrieve candidate exam convocations and submit door QR scan check-ins with complete validation, powered by the core check-in Single-Action.

**Blocked by:** 03: Versioned Mobile REST API (`/api/v1/`), Part 04 - 04: Interactive Mobile QR Scan Exam Check-in Interface

**Status:** completed

- [x] API route `/api/v1/exams/my-exams` returning student's published exams and assigned split rooms
- [x] API route `/api/v1/exams/convocation/{id}/download` streaming the official PDF convocation
- [x] API route `POST /api/v1/check-in/scan` accepting QR code UUID token and executing live door check-in
- [x] Clear JSON response indicating check-in success, wrong room warning, or superseded convocation error
- [x] Automated tests asserting API check-in success, invalid token rejection, and role authorization

## Design decisions (2026-10-02, Ticket 04)

- **Exam Feed & Split Room Allocations**:
  - `GET /api/v1/exams/my-exams` returns published exams concerning the authenticated student or teacher with ISO 8601 timestamps, module info, split room assignments, and personal seat details (`room`, `seat_number`, `convocation_uuid`, `convocation_status`, `checked_in_at`).
- **Official Convocation PDF Streaming**:
  - `GET /api/v1/exams/convocation/{id}/download` accepts either exam ID or candidate ID.
  - Verifies policy ability `downloadConvocation`.
  - Streams generated PDF document via `DownloadExamDocumentAction`.
  - Returns 409 RFC 7807 problem details when PDF generation is still pending in queue.
- **Mobile QR Door Check-in**:
  - `POST /api/v1/check-in/scan` accepts `uuid`, `token`, or `qr_code` (supports raw UUID or full signed URL).
  - Verifies convocation through `VerifyConvocationAction`.
  - Detects `SupersededConvocation` from emergency reschedules and returns structured 409 response with `status: 'superseded'`, current room allocation, and superseded timestamp.
  - Enforces `Gate::authorize('checkIn', $exam)` requiring `RecordAttendance` or `ManageExams`.
  - Executes `CheckInCandidateAction::execute()` ensuring atomic, single-scan door check-in.
  - Returns structured feedback with appropriate HTTP codes:
    - 200 OK with `status: 'success'` and candidate check-in details.
    - 422 with `status: 'wrong_room'` specifying the candidate's actual assigned room.
    - 422 with `status: 'already_checked_in'` and original check-in timestamp.
    - 422 with `status: 'closed'` if scanned outside the allowed check-in window.
    - 404 Problem Details if the scanned token does not exist.
    - 403 Problem Details if caller is unauthorized.
