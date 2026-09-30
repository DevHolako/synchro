# 02: Teacher Grade Entry Grid with Real-Time Calculations

**What to build:** Dedicated web gradebook interface for instructors to enter student marks out of 20 for their assigned exam, toggle absence flags, view real-time calculated final scores, and submit the draft to coordination for deliberation review.

**Blocked by:** 01: Configurable Assessment Weightings per Module, Part 04 - 01: Exam Period Definition and 5-State Exam Scheduling

**Status:** ready-for-agent

- [ ] ExamGrade model linking `exam_id`, `student_id`, `continuous_assessment_grade`, `exam_grade`, `final_grade`, `is_absent`, and `remarks`
- [ ] Web grade entry table displaying enrolled student roster with matricule and photo
- [ ] Numerical inputs with 2 decimal precision (0.00 to 20.00) and instant client-side composite calculation
- [ ] Absence toggle setting exam mark to zero with absence remark requirement
- [ ] Draft saving and "Submit to Coordination" action transitioning exam grading state to `submitted`
- [ ] Automated tests asserting mathematical rounding, teacher authorization, and draft state transitions
