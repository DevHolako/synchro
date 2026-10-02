# 02: Alphabetical Room Splitting and Anti-Clash Invigilator Allocation

**What to build:** Room allocation engine that partitions students alphabetically across multiple rooms when group headcount exceeds the primary room's `exam_capacity`. Includes an administrative `Force Single Room` override flag with audit logging, and enforces invigilator assignments (1 lead per room + assistant if > 25 students).

**Blocked by:** 01: Exam Period Definition and 5-State Exam Scheduling, Part 01 - 01: Campus, Building, and Room Infrastructure with Dual Capacities

**Status:** done

- [x] Single-Action `SplitExamRoomsAction` evaluating group size vs assigned rooms' `exam_capacity`
- [x] Alphabetical partitioning of enrolled students into allocated rooms (e.g. A–L in Room 1, M–Z in Room 2)
- [x] `Force Single Room` override option allowing admin to bypass room split with required audit justification
- [x] Invigilator assignment interface linking teachers to allocated exam rooms with role (`principal`, `adjoint`)
- [x] Anti-collision check preventing an invigilator from being scheduled in multiple rooms at the same time
- [x] Staffing threshold alert recommending an assistant invigilator if allocated room headcount > 25
- [x] Automated tests covering odd headcounts, multi-room splits, and invigilator conflict rejection

## Design decisions (2026-10-02, discussion round; all recommendations accepted)

- **Names**: `student_profiles.last_name` and `first_name` (added). They are required when provisioning or importing a student. The import columns become `last_name` and `first_name`, and `users.name` is set to "First LAST". The migration fills existing rows by splitting `users.name` at its last space. Teachers and staff keep a single name.
- **Rooms**: the coordinator picks an ordered list of rooms. Busy rooms (held by a course session or another booked exam) are flagged in the picker. Every chosen room is used, and rooms kept in a new choice keep their invigilators.
- **Split** (`SplitExamRoomsAction`):
  - candidates are the student accounts of the exam's groups;
  - sorted by surname, then given name, with a French `Collator` (accents and case ignored), and student number for exact ties;
  - shared in proportion to each room's `exam_capacity` by largest remainder (earlier rooms win ties), never above a room's capacity;
  - one unbroken range per room, with its first and last surnames;
  - seats are numbered from 1 in each room.
- **Seat shortage**: rooms that seat fewer than the candidates are refused. The only way past it is "Force Single Room" (`exams.force_single_room`, added): everyone goes in one room. It needs `OverrideSoftConflicts` and a justification, and every save is audited as a `forced_single_room` `ConflictOverride`.
- **Model**:
  - `exam_room_assignments`: position, count and surname range per room;
  - `exam_candidates`: one row per student with room and seat, the base for tickets 03 and 04;
  - `exam_invigilators`: `principal` or `adjoint`, a teacher at most once per exam.
- **When the split runs**: whenever the rooms are saved, again when the exam's groups or times change, and again on scheduling (to catch students who joined). It is frozen from publication on.
- **Lifecycle guards**:
  - scheduling needs rooms and at least one candidate;
  - publishing needs a lead invigilator in every room;
  - "publish all" skips exams still missing a lead and says how many.
- **Conflicts**:
  - booked exams also hold their rooms and invigilators (`ExamOccupancy`), against course sessions and other exams;
  - an invigilator teaching or invigilating elsewhere is a hard conflict once the exam is booked (drafts are not checked);
  - a declared unavailability is always a soft conflict, to override with a justification when the invigilator is assigned;
  - exam saves (`GuardExamConflictsAction`) stop only on hard conflicts.
- **Invigilators**: teacher accounts only; one lead and any number of assistants per room. An assistant is recommended above 25 students (warning only). Staff may change until the exam starts, even after publication.
- **Visibility and feed**: teachers also see the published exams they invigilate. "Mes examens" shows a student's room and seat, or an invigilator's room and role. The feed location is the student's room and seat, the invigilator's room, or otherwise the exam's rooms.
- **UI**: a "Salles & surveillants" side sheet. It holds the room picker (ordered, busy rooms flagged, Force Single Room with justification), the split per room, and per-room lead and assistant pickers with the assistant recommendation and the unavailability override. Rows show the rooms and a "lead missing" badge. The invite dialog asks students for surname and given name.
- **After the Part review**:
  - Busy rooms are flagged in the picker, not disabled: a draft books nothing, and a booked exam's rooms are checked on save.
  - Whenever a booked exam takes a new time (editing a scheduled exam, scheduling, emergency reschedule), invigilators who are busy then, or declared an unavailability not already overridden for this exam, are released and named in the toast (`ReleaseBusyInvigilatorsAction`, run by `GuardExamConflictsAction`). Rooms and groups still refuse the move.
  - Publishing and staffing lock the exam row, so a lead cannot leave between the check and the publication; "publish all" runs in one transaction and queues documents only for the exams it published.
