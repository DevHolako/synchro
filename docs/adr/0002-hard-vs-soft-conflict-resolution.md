# Hard vs Soft Conflict Resolution with Audit Trail

To preserve schedule integrity while allowing realistic administrative flexibility, we decided to partition scheduling conflicts into non-negotiable Hard Conflicts (physical double-booking of teacher, room, or student group returning HTTP 422) and overrideable Soft Conflicts (capacity overage, declared teacher unavailability, forced single-room exam bypass). Soft conflicts require explicit administrator override flags and are logged in an immutable audit trail.
