# 04: Interactive Mobile QR Scan Exam Check-in Interface

**What to build:** Authenticated mobile web check-in screen accessible when an invigilator scans a student's convocation QR code at the exam door. Displays candidate identity, validates assigned room, and allows recording live attendance with a single tap.

**Blocked by:** 03: Official PDF Convocation and Door Attendance Sheet Generation

**Status:** done

- [x] Route `/verify/convocation/{uuid}` accessible to authenticated invigilators and coordinators
- [x] Verification screen displaying student photo, matricule, full name, group, and assigned room
- [x] Clear visual warning if student scans in a room different from their allocated split room
- [x] One-tap "Mark Present" button updating student exam attendance live in the database
- [x] Audit timestamp and scanning invigilator ID recorded on check-in
- [x] Automated tests asserting valid check-in, wrong room alert, and duplicate check-in prevention

## Design decisions (2026-10-02, defaults confirmed with "go")

- **Screen**: the page a convocation's signed QR code opens (`/verify/convocation/{uuid}`) is the check-in screen, built for a phone. The phone's own camera app opens the link; there is no in-app scanner and no new dependency. Exam managers and the exam's invigilators may open it (`ExamPolicy::checkIn`).
- **Data**: `exam_candidates.checked_in_at` and `checked_in_by` (added). The check-in is a conditional update on "not yet present", so a second scan, or two invigilators at once, records it once; the screen then shows "Déjà présent depuis 08:47 (pointé par X)". Accounts that checked someone in keep their history.
- **Wrong room**: an invigilator of another room sees a red warning naming the expected room, and cannot check the student in (`CheckInCandidateAction::ensureAllowed`). Exam managers have no room and check in anyone.
- **Window**: published exams only, from 60 minutes before the start until the end, by the school's clock (`Exam::isCheckInOpen`). Outside it the screen shows the details with the button disabled.
- **Undo**: a check-in may be cancelled under the same rules while check-in is open (it clears both fields; no separate audit).
- **Room list**: `GET /exams/{exam}/rooms/{assignment}/check-in` (`exams/room-check-in`). It shows each candidate in seat order, present (with the time) or not yet arrived, a present/expected counter, and a manual "Marquer présent" for students without their convocation. It refreshes every 10 s (`usePoll`). It is open to managers and that room's invigilators, and linked from "Mes examens" (invigilators) and the allocation sheet (managers).
- **Photo**: deferred, as agreed; an initials avatar stands in.
- **After the exam**: check-ins are read-only; a candidate never checked in counts as absent (Part 05 will read this).
- **After the Part review**: an invigilator also needs `RecordAttendance` to check candidates in (exam managers need `ManageExams`), so revoking the permission removes access even while assigned.
