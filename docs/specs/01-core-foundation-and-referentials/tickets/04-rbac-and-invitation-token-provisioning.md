# 04: User Roles and Expiring Invitation Token Provisioning

**What to build:** Secure user account onboarding without public registration. Administrators provision accounts with assigned roles (`Administrator`, `Coordinator`, `Teacher`, `Student`) and the system generates cryptographically signed, expiring Invitation Tokens (valid for 72 hours) emailed to users for initial password setup.

**Blocked by:** 02: Academic Hierarchy: Departments, Programs with Modality, and Student Groups

**Status:** ready-for-agent

- [ ] Role-Based Access Control setup defining roles (`Administrator`, `Coordinator`, `Teacher`, `Student`)
- [ ] TeacherProfile and StudentProfile models linked to User
- [ ] Public registration route disabled; only authenticated administrators can provision users
- [ ] InvitationToken model and signed URL generation with 72-hour expiration window
- [ ] Initial password creation screen verifying token signature before account activation
- [ ] Resend invitation token and manual temporary password reset actions for administrators
- [ ] Automated tests asserting unauthorized access prevention, token expiration, and successful account activation
