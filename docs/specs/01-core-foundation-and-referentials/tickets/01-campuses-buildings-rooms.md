# 01: Campus, Building, and Room Infrastructure with Dual Capacities

**What to build:** End-to-end management of physical teaching spaces. Coordinators can view, create, edit, and deactivate Campuses, Buildings, and Rooms with explicit Course Capacity (standard lecture seating density) and Exam Capacity (distanced seating density) alongside equipment flags.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] Campus model, migration, factory, and seeder created with unique identifier and location attributes
- [ ] Building model associated with Campus
- [ ] Room model associated with Building with both `course_capacity` (integer > 0) and `exam_capacity` (integer > 0, <= course_capacity)
- [ ] Room equipment flags supported (e.g. `has_projector`, `is_lab`)
- [ ] Web management UI (React + Inertia) allowing coordinators to list, filter, create, and edit rooms
- [ ] Validation ensuring room names are unique per building and exam capacity does not exceed course capacity
- [ ] Automated tests asserting CRUD behavior, validation failures, and authorization policies
