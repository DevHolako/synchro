# 01: Configurable Assessment Weightings per Module

**What to build:** Administrative configuration allowing coordinators to define grading component weightings per module (Continuous Assessment / CC % and Final Exam %) ensuring total weighted calculation equals 20.

**Blocked by:** Part 01 - 03: Module Catalog with Syllabus Hours and Color Coding

**Status:** ready-for-agent

- [ ] Module schema attributes for `continuous_assessment_weight` and `exam_weight` (sum must equal 100%)
- [ ] Module settings UI enabling coordinators to configure or adjust weighting percentages
- [ ] Validation preventing invalid percentage totals or negative weights
- [ ] Automated tests asserting weighting validation rules and default fallback (100% Final Exam)
