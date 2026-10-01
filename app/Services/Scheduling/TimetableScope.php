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
    public function __construct(
        public TimetablePerspective $perspective,
        public ?int $subjectId,
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
            ? new self(TimetablePerspective::Teacher, $viewer->id)
            : new self(TimetablePerspective::Group, $studentProfile->student_group_id);
    }
}
