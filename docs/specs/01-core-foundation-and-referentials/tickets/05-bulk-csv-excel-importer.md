# 05: Bulk Spreadsheet Importer for Referentials and Users

**What to build:** Upload wizard allowing administrators to import CSV/Excel spreadsheets of Rooms, Modules, Students, and Teachers in bulk, validating file format and relations in an atomic transaction before dispatching user invitations.

**Blocked by:** 03: Module Catalog with Syllabus Hours and Color Coding, 04: User Roles and Expiring Invitation Token Provisioning

**Status:** completed

- [x] Import wizard UI supporting file drag-and-drop (.xlsx and .csv)
- [x] Single-Action `ImportReferentialsAction` validating spreadsheet column schemas
- [x] Bulk room importer with validation of dual capacities
- [x] Bulk user importer with automatic creation of profiles (Teacher, Student with Group assignment) and queued invitation tokens
- [x] Detailed error reporting identifying exact invalid row numbers on parse failure
- [x] Automated tests asserting successful multi-row import and full database rollback on malformed rows
