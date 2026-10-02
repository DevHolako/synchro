# 02: Teacher Grade Entry Grid with Real-Time Calculations

**What to build:** Dedicated web gradebook interface for instructors to enter student marks out of 20 for their assigned exam, toggle absence flags, view real-time calculated final scores, and submit the draft to coordination for deliberation review.

**Blocked by:** 01: Configurable Assessment Weightings per Module, Part 04 - 01: Exam Period Definition and 5-State Exam Scheduling

**Status:** done

- [x] ExamGrade model linking `exam_id`, `student_id`, `continuous_assessment_grade`, `exam_grade`, `final_grade`, `is_absent`, and `remarks`
- [x] Web grade entry table displaying enrolled student roster with matricule and photo
- [x] Numerical inputs with 2 decimal precision (0.00 to 20.00) and instant client-side composite calculation
- [x] Absence toggle setting exam mark to zero with absence remark requirement
- [x] Draft saving and "Submit to Coordination" action transitioning exam grading state to `submitted`
- [x] Automated tests asserting mathematical rounding, teacher authorization, and draft state transitions

## Design decisions (2026-10-02, design round before the ticket)

- **Sheet and status**: `exam_deliberations` holds one row per exam, created the first time the sheet is opened. Its `status` is `GradeSheetStatus` (`draft`, `submitted`, `locked`), with `submitted_at` and `submitted_by`. Ticket 03 adds the lock and PV columns to the same row.
- **Lines**: `exam_grades` rows are unique on (exam, student), with grades as `decimal(4,2)` and no foreign key to `exam_candidates`. While the sheet is a draft, each opening adds a line for every candidate who has none, in one insert.
- **When the grid opens**: once the exam is finished (`Exam::isGradable()`: completed or archived) and belongs to a `normal` period. Retake sheets come with ticket 04.
- **Absences**: a new line starts absent when the candidate never checked in, but only if the door check-in was used for the exam. An absent student's exam grade is cleared and counts as 0, and the CC share is kept.
- **Who**:
  - `ExamPolicy::enterGrades`: `EnterGrades` and being the module's `teacher_id`.
  - `ExamPolicy::viewGrades`: the same, or `ManageExams`, or `LockGrades` (read-only).
  - If the module's teacher changes, the new teacher takes over the sheet.
- **Saving**:
  - "Enregistrer le brouillon" sends only the changed lines (`PUT /exams/{exam}/grades`, one upsert), and the server computes the finals.
  - Grades go from 0 to 20 with at most two decimals, and "14,5" is accepted.
  - The page asks before leaving with unsaved lines; there's no autosave.
- **Submitting** (`POST /exams/{exam}/grades/submit`):
  - Every line needs its CC grade (when CC counts) and either an exam grade or an absence with a remark. A refusal names up to five students.
  - The teacher can't undo a submission; only a coordinator can send the sheet back (ticket 03).
- **Formula**:
  - `CalculateFinalGradeAction` works on integer hundredths and rounds half-up.
  - `final-grade.ts` mirrors it for the live grid, but the stored value is always the server's.
- **Weight changes**: `UpdateModuleAction` recomputes the stored finals of the module's draft and submitted sheets, in the same transaction (`RecomputeOpenFinalGradesAction`). Locked sheets keep their finals.
- **Concurrency**: every grade write locks the exam row, then the sheet (`LockDraftGradeSheetAction`), and is refused with a translated error once the sheet is no longer a draft.
- **UI**:
  - `resources/js/pages/grades/` holds the grid: header with status, live stats (complete lines, absences, average, passing), a memoized row per student with an initials avatar, and a sticky save/submit bar with a confirmation dialog.
  - The exams list shows "Saisir les notes" or "Voir les notes" with the sheet's status.
- **No audit for drafts**: only `updated_at` per line and the submission stamp. The audit starts at the lock (ticket 03).
