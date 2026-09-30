# 02: Core Conflict Detector Service for Physical Clashes

**What to build:** High-performance, synchronous collision detection service evaluating physical resource clashes in under 80 milliseconds. Rejects simultaneous bookings of the same Teacher, Room, or Student Group with a strict HTTP 422 Hard Conflict response.

**Blocked by:** Part 01 - 01: Campus, Building, and Room Infrastructure with Dual Capacities, Part 01 - 02: Academic Hierarchy

**Status:** ready-for-agent

- [ ] `ConflictDetectorService` exposing unified method `checkConflicts(targetData): ConflictResult`
- [ ] Hard conflict detector: Room collision (already occupied in overlapping time window)
- [ ] Hard conflict detector: Teacher collision (already assigned to another active session or exam)
- [ ] Hard conflict detector: Student Group collision (group scheduled for another simultaneous session)
- [ ] Sub-80ms execution benchmark using optimized composite database indexes
- [ ] Automated tests covering edge-case boundary intervals (adjacent slots without overlap vs 1-minute overlap)
