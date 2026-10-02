# 01: Configurable Assessment Weightings per Module

**What to build:** Administrative configuration allowing coordinators to define grading component weightings per module (Continuous Assessment / CC % and Final Exam %) ensuring total weighted calculation equals 20.

**Blocked by:** Part 01 - 03: Module Catalog with Syllabus Hours and Color Coding

**Status:** done

- [x] Module schema attributes for `continuous_assessment_weight` and `exam_weight` (sum must equal 100%)
- [x] Module settings UI enabling coordinators to configure or adjust weighting percentages
- [x] Validation preventing invalid percentage totals or negative weights
- [x] Automated tests asserting weighting validation rules and default fallback (100% Final Exam)

## Design decisions (2026-10-02, design round before the ticket)

- **One stored column**: `modules.continuous_assessment_weight` (whole percent, `unsignedTinyInteger`, default 0). The exam weight is derived (`Module::exam_weight` = 100 − CC) and sent with the module, so the two can never disagree. This departs from the spec's two columns on purpose: "sum must equal 100%" holds by construction.
- **The exam always counts**: CC goes from 0 to 99 (`Module::MAX_CONTINUOUS_ASSESSMENT_WEIGHT`). Grade sheets hang on exams (Part-wide decision), so a 100% CC module could not be graded; such modules (projects, internships) are deferred.
- **Default**: 0, so new modules and every existing module are 100% final exam.
- **Who configures**: no new permission. The weighting is a module field behind the module's `update` policy (Administrator and Coordinator).
- **Validation**: whole percentages, 0–99, in the form requests (translated `messages.module_continuous_assessment_weight_range`) and again in `CreateModuleAction` / `UpdateModuleAction`.
- **Import**: the modules spreadsheet takes an optional `continuous_assessment_weight` column (empty means 0); an out-of-range value is a row error.
- **UI**: an "Évaluation" section in the module dialog (`module-grading-section.tsx`: CC input, exam share read-only, inline range error, save disabled while out of range). The table shows "CC 40 % · Examen 60 %" under the syllabus hours. The limit comes from PHP (`limits.max_continuous_assessment_weight`).
- **Changing weights later**: allowed at any time. Locked grade sheets keep their stored finals, and the deliberation snapshots the weight it used for the PV (ticket 03). A weight change recomputes the stored finals of the module's draft and submitted sheets (ticket 02).
