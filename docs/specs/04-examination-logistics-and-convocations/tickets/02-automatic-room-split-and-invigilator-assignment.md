# 02: Alphabetical Room Splitting and Anti-Clash Invigilator Allocation

**What to build:** Room allocation engine that partitions students alphabetically across multiple rooms when group headcount exceeds the primary room's `exam_capacity`. Includes an administrative `Force Single Room` override flag with audit logging, and enforces invigilator assignments (1 lead per room + assistant if > 25 students).

**Blocked by:** 01: Exam Period Definition and 5-State Exam Scheduling, Part 01 - 01: Campus, Building, and Room Infrastructure with Dual Capacities

**Status:** ready-for-agent

- [ ] Single-Action `SplitExamRoomsAction` evaluating group size vs assigned rooms' `exam_capacity`
- [ ] Alphabetical partitioning of enrolled students into allocated rooms (e.g. A–L in Room 1, M–Z in Room 2)
- [ ] `Force Single Room` override option allowing admin to bypass room split with required audit justification
- [ ] Invigilator assignment interface linking teachers to allocated exam rooms with role (`principal`, `adjoint`)
- [ ] Anti-collision check preventing an invigilator from being scheduled in multiple rooms at the same time
- [ ] Staffing threshold alert recommending an assistant invigilator if allocated room headcount > 25
- [ ] Automated tests covering odd headcounts, multi-room splits, and invigilator conflict rejection
