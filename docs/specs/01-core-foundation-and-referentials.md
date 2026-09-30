# Spec 01: Core Foundation & Referentials

## Problem Statement

Academic coordinators and department heads currently manage campuses, academic programs, student groups, modules, and room inventories using fragmented spreadsheets and email threads. This leads to duplicate records, mismatched room capacities, lack of distinction between standard lecture capacity and distanced exam capacity, and insecure user account distribution with shared passwords.

## Solution

A centralized administrative referential module within Synchro that models the entire academic organizational structure (Campuses, Buildings, Rooms with dual lecture/exam capacities, Departments, Programs with Program Modality, Student Groups, and Modules with syllabus hours). User management is restricted to administrative provisioning via bulk CSV/Excel imports with cryptographically signed, expiring Invitation Tokens for first-time password setup, governed by strict Role-Based Access Control (RBAC).

## User Stories

1. As an Administrator, I want to manage Campuses, Buildings, and Rooms, so that all physical teaching spaces are centrally registered.
2. As an Administrator, I want to define both a Course Capacity and an Exam Capacity for each Room, so that the system enforces appropriate density during lectures and distanced seating during examinations.
3. As an Administrator, I want to specify room equipment (projector, computer lab, board type), so that classes requiring specific facilities are placed appropriately.
4. As an Administrator, I want to create Departments and Programs, indicating their Program Modality ("Temps Aménagé" or "Formation Initiale"), so that schedule analysis and reporting reflect the academic regime.
5. As an Administrator, I want to manage Student Groups under each Program with designated enrollment counts, so that group schedules can be tracked against room sizes.
6. As an Administrator, I want to register Modules under Programs with defined syllabus hours (total lecture hours, TP hours) and UI color codes, so that module progress can be visually monitored.
7. As an Administrator, I want to perform bulk CSV/Excel imports for Rooms, Modules, Teachers, and Students, so that academic year onboarding does not require manual entry of hundreds of records.
8. As an Administrator, I want the bulk import tool to validate data format and reject invalid rows with descriptive error messages, so that database consistency is preserved.
9. As an Administrator, I want public self-registration to be strictly disabled, so that only verified university members access the system.
10. As an Administrator, I want the system to generate secure, expiring Invitation Tokens when users are imported or created, so that each user receives a private email link to establish their initial password.
11. As a Teacher or Student, I want to follow my secure Invitation Token link to set my password and activate my account, so that I can log into Synchro safely.
12. As an Administrator, I want to regenerate and resend an Invitation Token or issue a temporary password for a user, so that I can resolve onboarding issues.
13. As a User, I want to log in securely with email and password and manage my profile details, so that my contact information remains up to date.

## Implementation Decisions

- **Architectural Seam**: Core domain operations are driven through Single-Action classes (`ImportReferentialsAction`, `ProvisionUserWithInvitationAction`, `ConsumeInvitationTokenAction`, `CreateRoomAction`).
- **Access Control & RBAC**: Dedicated roles (`Administrator`, `Coordinator`, `Teacher`, `Student`) implemented via standard Laravel policies and authorization gates.
- **Dual Room Capacities**: Every room record mandates `course_capacity` (maximum seated density for lectures) and `exam_capacity` (distanced seating density, typically 50% of course capacity).
- **Program Modality**: Programs store an enum attribute `program_modality` (`temps_amenage`, `formation_initiale`) used for filtering and syllabus calculations (ADR 0001).
- **Authentication & Onboarding**: Public registration is disabled; users transition through an account lifecycle (`invited`, `active`, `suspended`) activated via cryptographically signed Invitation Tokens valid for 72 hours (ADR 0007).
- **Bulk Import Engine**: Queued spreadsheet import service validating headers, mapping foreign relations (e.g. program to department), and dispatching welcome emails asynchronously.

## Testing Decisions

- **Seam**: Highest HTTP level testing using Pest/PHPUnit HTTP tests against authenticated endpoints and queued mail dispatch.
- **Coverage**:
  - Multi-campus room creation verifying validation of dual capacities.
  - Bulk CSV import test parsing valid and malformed files, verifying rollback on failure.
  - Invitation token generation, signed URL expiry verification, and password setup flow.
  - Policy tests verifying that Students and Teachers cannot mutate referential data.

## Out of Scope

- Tuition fee tracking, billing, or financial ledger operations.
- Teacher payroll or compensation calculations for hourly/vacataire staff.
- Physical door lock or IoT room sensor integration.

## Further Notes

- Seeding scripts must provide realistic ISGA test datasets (Casablanca campus, ISI department, 1CI / 2CI programs, standard amphitheatres and classrooms).
