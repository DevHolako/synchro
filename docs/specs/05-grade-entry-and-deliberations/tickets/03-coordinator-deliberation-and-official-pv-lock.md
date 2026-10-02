# 03: Coordinator Deliberation Review, Official PV PDF Archival, and Grade Locking

**What to build:** Deliberation interface allowing coordinators to inspect submitted grades, view statistical summaries (class average, pass rate, failure count), formally lock the deliberation, generate the official signed PDF PV, and publish final marks to student portals.

**Blocked by:** 02: Teacher Grade Entry Grid with Real-Time Calculations

**Status:** done

- [x] Deliberation dashboard displaying submitted grades with class statistics (average, median, pass rate)
- [x] Single-Action `LockDeliberationAction` setting `locked_at` and coordinator signature record
- [x] Permanent immutability: locked grades reject all subsequent edit attempts (HTTP 403)
- [x] Automated generation of the official Procès-Verbal (PV) PDF document matching ISGA academic format
- [x] Secure archiving of the PV PDF to permanent disk storage
- [x] Student grade portal unlocked upon PV lock, displaying published module marks
- [x] Automated tests asserting locking immutability, PDF PV creation, and student visibility gates

## Design decisions (2026-10-02, defaults confirmed with "go")

- **Permissions**:
  - `LockGrades` joins the Coordinator bundle and gates the lock, the send-back (`ExamPolicy::lockGrades`) and the deliberation board.
  - New `ViewOwnGrades` (`view:own-grades`) in the Student bundle gates "Mes notes".
  - Both are mirrored in `resources/js/lib/permissions.ts`.
- **Board** (`/deliberations`, sidebar "Délibérations"):
  - Shows a normal period's finished exams and where each sheet stands: not started, draft, to deliberate, locked. Sheets waiting for a decision come first.
  - Counts per status, plus the class average and pass rate once locked.
  - "Ouvrir" leads to the grid, which coordinators see read-only with a deliberation panel.
- **Figures** (`CalculateDeliberationStatsAction`): average, median, pass rate, and counts of passing, failing and absent students, computed on integer hundredths and rounded half-up.
- **Send-back** (`POST /exams/{exam}/grades/return`): a submitted sheet returns to draft with a reason of 10–1000 characters (`returned_at`, `return_reason`). The teacher sees the reason in a banner until they submit again, which clears it.
- **Lock** (`POST /exams/{exam}/deliberation/lock`, `LockDeliberationAction`):
  - Only a submitted sheet can be locked. The lock sets `locked_at` and `locked_by`, and snapshots the CC weight, `class_average` and `pass_rate`.
  - The PV job is queued after commit.
  - The grid of a locked sheet shows the weighting it was locked with.
- **Immutability**:
  - `enterGrades` refuses a locked sheet, so a PUT or submit gets 403.
  - `ExamGrade` refuses updates and deletes through the model once its sheet is locked.
  - `ExamDeliberation` refuses any change after the lock, except filling in its PV once.
  - The appeal flow is out of scope.
- **PV**:
  - `RenderDeliberationPvPdfAction` uses the `pdf.deliberation-pv` view (French, `documents.pv_*`). It holds the exam, the weighting, one line per student with CC, exam or ABS, final and result (Admis/Rattrapage), the figures, who submitted, and the coordinator's signature block.
  - `GenerateDeliberationPvJob` (`default` queue, 45 s) calls `StoreDeliberationPvAction`, which writes `deliberation-pvs/{exam}.pdf` to the private disk and stores `pv_document_path` and `pv_sha256`. It's idempotent.
  - `GET /exams/{exam}/pv` is open to `LockGrades`, `ManageExams` and the module teacher.
- **Students** (`/my-grades`, sidebar "Mes notes"): only lines of locked deliberations, with CC, exam or ABS, final, result and the locked weighting. Nothing appears before the lock.
- **Left out**: notifications on send-back or publication (Part 06), the appeal flow, and archiving waiting for deliberations.

## After the Part review

- Immutability moved from model events to the builders (`GradeLineBuilder`, `DeliberationBuilder`). Bulk writes on locked lines or deliberations are refused, except filling in the PV once.
- Retake deliberations say "Ajournés" in the PV summary and in the coordinator's figures.
- The PV's "submitted by" and the coordinator's signature block print official names.
