# 04: User Roles and Expiring Invitation Token Provisioning

**What to build:** Secure user account onboarding without public registration. Administrators provision accounts with assigned roles (`Administrator`, `Coordinator`, `Teacher`, `Student`) and the system generates cryptographically signed, expiring Invitation Tokens (valid for 72 hours) emailed to users for initial password setup.

**Blocked by:** 02: Academic Hierarchy: Departments, Programs with Modality, and Student Groups

**Status:** completed

- [x] Role-Based Access Control setup defining permissions as gates of check and roles (`Administrator`, `Coordinator`, `Teacher`, `Student`) as permission bundles
- [x] TeacherProfile and StudentProfile models linked to User
- [x] Public registration route disabled; only authenticated administrators can provision users
- [x] InvitationToken model and signed URL generation with 72-hour expiration window
- [x] Initial password creation screen verifying token signature before account activation
- [x] Resend invitation token and manual temporary password reset actions for administrators
- [x] Automated tests asserting unauthorized access prevention, token expiration, and successful account activation
