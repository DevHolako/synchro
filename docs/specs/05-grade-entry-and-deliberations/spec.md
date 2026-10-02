# Spec 05: Grade Entry, Deliberations & Official PV

## Problem Statement

Grade entry and deliberation processing at universities often relies on loose spreadsheets sent via email. This creates serious vulnerabilities: calculation errors in module weightings, transcript discrepancies between draft and final marks, premature student access to unapproved grades, and absence of an official, tamper-proof archived Procès-Verbal (PV).

## Solution

A structured two-step Grade Entry and Deliberation module. Instructors enter marks and attendance status (Present, Absent) into a dedicated web grid with automatic calculation of final module scores out of 20 based on configurable component weightings (e.g. Continuous Assessment / CC + Final Exam). Grades remain strictly private and provisional until an authorized Coordinator reviews, locks the deliberation, signs the official Procès-Verbal (PV), and archives it as an immutable PDF, which releases grades for student consultation.

## User Stories

1. As an Administrator, I want to configure grade weighting components per module (e.g. 40% Continuous Assessment / CC + 60% Final Exam, or 100% Final Exam), so that grading respects pedagogical syllabi.
2. As a Teacher, I want to access a web grading grid for my assigned exam, displaying all enrolled students with their photo and matricule, so that I can enter marks efficiently.
3. As a Teacher, I want to input grades out of 20 with decimal precision (e.g. 14.50) and toggle an `is_absent` flag, so that unsubmitted papers or absences are accurately logged.
4. As a Teacher, I want the system to calculate the final composite grade automatically as I type, so that I can immediately verify student outcomes.
5. As a Teacher, I want to save grade drafts and submit the finalized draft to the Coordinator, so that my submissions undergo formal review.
6. As a Coordinator, I want to review submitted grade sheets, inspect calculated class averages, pass rates, and identify failing students, so that deliberations are data-driven.
7. As a Coordinator, I want to formally Lock and Sign the Deliberation, so that the grades become permanently immutable.
8. As a Coordinator, I want the system to automatically generate the official Procès-Verbal (PV) PDF with full grade listings, statistical summaries, and coordinator signature block upon locking, so that it is permanently archived.
9. As a Student, I want to view my published module grades in my personal portal only after the deliberation is officially locked, so that I never see provisional unverified marks.
10. As a Coordinator, I want to automatically flag students qualifying for the Retake Session ("Rattrapage") based on passing thresholds (grade < 10/20), so that retake exam lists are generated without manual calculations.

## Implementation Decisions

- **Two-Step Deliberation Workflow (ADR 0006)**:
  - Phase 1: Teacher draft entry and submission.
  - Phase 2: Coordinator review, deliberation locking, and official PV archiving.
- **Entities & Schema**:
  - `modules`: `continuous_assessment_weight` (percentage), `exam_weight` (percentage).
  - `exam_grades`: `exam_id`, `student_id`, `continuous_assessment_grade`, `exam_grade`, `final_grade`, `is_absent`, `remarks`.
  - `exam_deliberations`: `exam_id`, `coordinator_id`, `locked_at`, `pv_document_path`, `class_average`, `pass_rate`.
- **Validation Rules**: Grades must be numerical between 0.00 and 20.00; if `is_absent=true`, final score is marked as zero with absence justification requirement.
- **Student Access Gate**: Student grade endpoints strictly verify `exam.deliberation.locked_at !== null`.
- **Official PV Document Generation**: Compiles institutional PV PDF matching ISGA academic standards, archived to encrypted local or S3 disk storage, and referenced in the deliberation record.

## Testing Decisions

- **Seam**: HTTP integration tests on `GradeEntryController` and `DeliberationController`.
- **Coverage**:
  - Mathematical precision of weighted grade formulas (rounding rules to 2 decimal places).
  - Teacher authorization (only the designated module teacher can submit draft marks).
  - Coordinator locking action asserting database state becomes immutable (subsequent PUT requests return 403 Forbidden).
  - Student visibility boundary asserting 404/empty state before deliberation lock, and full visibility after lock.
  - Automatic identification of failing students eligible for retake sessions.

## Out of Scope

- Credit transfer evaluation from other external universities.
- Degree printing on certified parchment paper with anti-counterfeit micro-threads.

## Further Notes

- In compliance with academic regulations, once a PV is locked, any subsequent modification requires a formal presidential grade appeal action logged in the audit trail.

## Part-wide decisions (2026-10-02, design round before ticket 01)

- **Working mode**: a full design round for tickets 01 and 02; for tickets 03–04, defaults are posted and confirmed with "go".
- **What a grade hangs on**: one grade sheet per exam. `exam_grades` rows are unique on (exam, student), and the sheet's roster is the exam's candidates. There's no foreign key to `exam_candidates`, which are rebuilt by every new room split. The CC grade sits on the same row and is entered in the same grid. A module with two exams in one period gets two sheets and two PVs.
- **Absence**: the exam grade counts as 0 and the CC share is kept, so one formula covers everyone. `is_absent` is prefilled from the door check-in (no check-in means absent), the teacher can override it, and an absence needs a remark.
- **Who enters and who locks**: grades are entered by holders of `EnterGrades` who are the module's `teacher_id`. `LockGrades` joins the Coordinator bundle. Coordinators don't type grades: they can send a submitted sheet back to draft.
- **Student photo**: still deferred (as in Part 04). The grid shows initials, the official name and the matricule.
- **PV**: stored on the private `local` disk, unencrypted, like convocations. "Signed" means a signature block (coordinator's official name and lock time) plus the PV's SHA-256 saved on the deliberation for tamper evidence. No e-signature.
- **Immutability**: locked grades and deliberations use the append-only `ImmutableBuilder` pattern, and edits answer 403. The presidential grade appeal flow is out of scope (deferred).
- **Retakes**: the rattrapage grade replaces the exam grade, CC carries over, the final is recomputed, and the student keeps the higher of the two finals.
- **Rounding**: final = (CC × w + exam × (100 − w)) / 100, rounded half-up to 2 decimals. One PHP calculator computes it on integer hundredths. The grid mirrors it in TypeScript for the live display, but the server's value is stored, recomputed on every save.
- **Pass mark**: final ≥ 10/20, one constant, no eliminatory mark.
- **Weights**: one stored column, CC 0–99, default 0, editable at any time (see ticket 01).
