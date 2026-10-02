<?php

namespace App\Services\Scheduling;

use App\Enums\TimetablePerspective;
use App\Models\User;
use InvalidArgumentException;

/**
 * The one group, teacher, room or campus whose sessions a timetable shows.
 *
 * "Mine" is resolved into a concrete scope first, so a scope never holds that perspective.
 * A null subject means nothing has been picked yet (or a student has no group): no sessions.
 */
final readonly class TimetableScope
{
    /**
     * @param  bool  $ownTimetable  Resolved from the viewer's "mine" perspective.
     */
    public function __construct(
        public TimetablePerspective $perspective,
        public ?int $subjectId,
        public bool $ownTimetable = false,
    ) {
        if ($perspective === TimetablePerspective::Mine) {
            throw new InvalidArgumentException('Resolve the "mine" perspective with TimetableScope::mine().');
        }
    }

    /**
     * The viewer's own timetable: a student's group, otherwise the sessions they teach.
     */
    public static function mine(User $viewer): self
    {
        $studentProfile = $viewer->studentProfile;

        return $studentProfile === null
            ? new self(TimetablePerspective::Teacher, $viewer->id, ownTimetable: true)
            : new self(TimetablePerspective::Group, $studentProfile->student_group_id, ownTimetable: true);
    }

    /**
     * The group whose timetable this is, if any (the syllabus panel follows it).
     */
    public function groupId(): ?int
    {
        return $this->perspective === TimetablePerspective::Group ? $this->subjectId : null;
    }

    /**
     * A student looking at their own timetable before being assigned to a group.
     */
    public function isStudentWithoutGroup(): bool
    {
        return $this->ownTimetable && $this->perspective === TimetablePerspective::Group && $this->subjectId === null;
    }
}
