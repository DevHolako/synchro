# 02: Academic Hierarchy: Departments, Programs with Modality, and Student Groups

**What to build:** End-to-end management of the academic structure. Coordinators can create Departments, define Programs with their Program Modality ("Temps Aménagé" vs "Formation Initiale"), and establish Student Groups with designated student enrollment headcounts.

**Blocked by:** 01: Campus, Building, and Room Infrastructure with Dual Capacities

**Status:** ready-for-agent

- [ ] Department model, migration, factory, and seeder created
- [ ] Program model associated with Department, storing `program_modality` enum (`temps_amenage`, `formation_initiale`)
- [ ] StudentGroup model associated with Program with academic year and `expected_headcount`
- [ ] Web management UI for browsing departments, programs, and nested groups
- [ ] Automated tests verifying cascade relationships, validation rules, and modality filtering
