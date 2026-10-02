# 03: Official PDF Convocation and Door Attendance Sheet Generation

**What to build:** Automated PDF generation producing print-ready student Convocations featuring personalized exam timetables, assigned split room details, and cryptographic verification QR codes, alongside door sign-in attendance rosters (Feuille d'émargement).

**Blocked by:** 02: Alphabetical Room Splitting and Anti-Clash Invigilator Allocation

**Status:** done

- [x] PDF generation service utilizing clean print-ready templates with ISGA official header
- [x] Personalized Convocation PDF per student with timetable, allocated room, seat reference, and instructions
- [x] Cryptographic QR code generated on each convocation encoding signed verification URL with HMAC SHA-256
- [x] Official Attendance Roster (Feuille d'émargement) PDF grouped by allocated room for paper sign-in backup
- [x] Student portal download button allowing enrolled students to download their Convocations once exam is `Published`
- [x] Automated tests verifying PDF compilation, binary output validity, and QR payload integrity

## Design decisions (2026-10-02, defaults confirmed with "go")

- **Libraries**: `barryvdh/laravel-dompdf` (dompdf 3.1, pure PHP, nothing added to the Docker image) and `bacon/bacon-qr-code` (SVG QR codes, no GD). Font subsetting is on, which brings a convocation from about 860 KB down to 26 KB.
- **One convocation per exam**: an A4 page with the ISGA header, period, identity (name, matricule, group), module, date and time, room and seat, standard instructions, and one QR code. Official documents are always in French (`lang/*/documents.php`, rendered with the `fr` locale).
- **QR code**: `exam_candidates.convocation_uuid` (added) inside a Laravel signed link (`URL::signedRoute`, HMAC-SHA256 with the app key, no expiry) to `/verify/convocation/{uuid}`. A tampered link gets 403 from the `signed` middleware; an unknown UUID gets 404. A new split gives each candidate a new UUID; publication freezes them.
- **Verification**: `VerifyConvocationAction` and `convocations/verify`, a phone-first page showing identity, exam, room and seat. It is open to exam managers and the exam's invigilators (`ExamPolicy::verifyConvocation`). Ticket 04 turns it into the check-in screen.
- **Generation**:
  - Publishing an exam, alone or with its period, queues after commit one `GenerateConvocationJob` per candidate and one `GenerateExamRosterJob`, on the `default` queue (45 s timeout, below the supervisor's 60 s).
  - The jobs only call `StoreConvocationAction` / `StoreExamRosterAction`, which skip files that already exist, so a redelivered job does nothing.
  - Files go to the private disk: `convocations/{exam}/{uuid}.pdf` and `exam-rosters/{exam}.pdf`.
- **Room sheets**: one PDF per exam. Each room gets a door list (surname range, then seat, name and group) and an attendance sheet (seat, name, matricule, signature column, present/absent counts, a blank invigilators' signature box kept on one page). Invigilator names are left off, so changing an invigilator never makes the sheet stale.
- **Downloads**:
  - `GET /exams/{exam}/convocation`: the signed-in candidate, once published.
  - `GET /exams/{exam}/roster`: exam managers, and the exam's invigilators once published.
  - The exams list shows "Ma convocation (PDF)" or "Feuilles d'émargement (PDF)", or "en préparation" until the file exists; a direct request in the meantime is sent back with an info toast.
- **After the Part review**:
  - Documents print the official name (see the spec's review alignment).
  - Students and invigilators also need `ViewExams` to download (exam managers download with `ManageExams`); download file names are translated (`documents.*_filename`).
  - Document jobs log a failure (`failed()`).
