# Support Dual Program Modalities and Campuses

To support both ISGA's executive evening/weekend programs ("Temps Aménagé") and standard daytime programs ("Formation Initiale") across current and future sites, we decided to introduce a `ProgramModality` enum/attribute and a lightweight `Campus` entity. This ensures timetable grids, allowed scheduling hours, and coordinator access scopes adapt cleanly without architectural rework when rolling out to regular tracks or other campuses.
