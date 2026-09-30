# 03: Module Catalog with Syllabus Hours and Color Coding

**What to build:** End-to-end module definition. Coordinators can assign Modules to Programs, define total syllabus lecture and practical work (TP) hours, specify assigned instructors, and select a hex color code used for timetable calendar visualization.

**Blocked by:** 02: Academic Hierarchy: Departments, Programs with Modality, and Student Groups

**Status:** completed

- [x] Module model with `code`, `name`, `total_hours`, `lecture_hours`, `tp_hours`, and `color_code`
- [x] Relationship between Module and Program
- [x] Assigned teacher relationship on Module
- [x] Web UI allowing coordinators to manage modules and preview calendar badge colors
- [x] Automated tests asserting syllabus hour validation and unique module codes per program
