# 04: Automated Retake Session (Rattrapage) Candidate Roster

**What to build:** Automated identification and grouping of students with failing grades (final score < 10/20) upon deliberation locking, pre-populating candidate rosters for Retake Exam scheduling without manual calculations.

**Blocked by:** 03: Coordinator Deliberation Review, Official PV PDF Archival, and Grade Locking

**Status:** done

- [x] Automatic identification query filtering candidates with `final_grade < 10.00` upon deliberation lock
- [x] Retake candidate roster view for coordinators per module and group
- [x] One-click action to create a Retake Exam in the corresponding Retake Exam Period pre-enrolled with failing students
- [x] Automated tests asserting accurate student filtering based on passing thresholds and retake exam population

## Design decisions (2026-10-02, defaults confirmed with "go")

- **Candidates** (`ListRetakeCandidatesAction`): students whose locked final is below 10.00 for a module, in a normal session of the retake period's academic year. When a module was examined twice, the latest line counts. Absent students qualify when their final is below 10. Unlocked sheets never count.
- **No enrollment table**: for exams in a rattrapage period, `ResplitExamAction::candidates()` keeps only the qualifying students of the exam's groups. That's computed again on every split and frozen at publication like any exam. The exam still books its whole groups in the conflict engine (safe, but broad; student-level booking is left out).
- **Roster** (`/retakes`, sidebar "Rattrapages", `ManageExams`): for a retake period, each module's failing students by group (name, matricule, normal-session final), and its retake exam's state once created.
- **One-click exam**: "Créer l'examen de rattrapage" asks for the date and times and posts to the existing `POST /exams` with the module and the failing students' groups. It saves a draft, and rooms, staffing and publication then work as usual. There's no bulk creation for every module.
- **Grading retakes** (the restriction from ticket 02 is lifted):
  - Opening a retake sheet carries each student's normal-session CC and final onto the line (`exam_grades.previous_final_grade`).
  - The CC is read-only; any CC sent is ignored.
  - The stored final is the better of the recomputed final and the normal-session one, in `CalculateFinalGradeAction` and its TypeScript mirror. The grid shows "Session normale : x" under the final.
  - Only the retake grade, or a remarked absence, is required to submit.
- **Deliberation, PV and "Mes notes"**: the board lists rattrapage periods too. A retake PV is titled for the retake session, with results "Admis" or "Ajourné". "Mes notes" marks retake lines.
