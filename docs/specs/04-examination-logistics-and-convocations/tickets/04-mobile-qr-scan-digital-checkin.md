# 04: Interactive Mobile QR Scan Exam Check-in Interface

**What to build:** Authenticated mobile web check-in screen accessible when an invigilator scans a student's convocation QR code at the exam door. Displays candidate identity, validates assigned room, and allows recording live attendance with a single tap.

**Blocked by:** 03: Official PDF Convocation and Door Attendance Sheet Generation

**Status:** ready-for-agent

- [ ] Route `/verify/convocation/{uuid}` accessible to authenticated invigilators and coordinators
- [ ] Verification screen displaying student photo, matricule, full name, group, and assigned room
- [ ] Clear visual warning if student scans in a room different from their allocated split room
- [ ] One-tap "Mark Present" button updating student exam attendance live in the database
- [ ] Audit timestamp and scanning invigilator ID recorded on check-in
- [ ] Automated tests asserting valid check-in, wrong room alert, and duplicate check-in prevention
