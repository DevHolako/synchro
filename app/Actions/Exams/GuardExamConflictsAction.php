<?php

namespace App\Actions\Exams;

use App\Exceptions\HardConflictException;
use App\Models\Exam;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;
use App\Services\Scheduling\ConflictDetectorService;
use App\Services\Scheduling\ConflictResult;

/**
 * Books an exam's resources at its current time: invigilators who are busy or unavailable then
 * are released (and named), and the exam goes through only when none of its rooms or groups is
 * taken.
 *
 * Must run inside the caller's transaction, after the caller locked the exam row
 * (`Exam::lockRow()`) and wrote its rooms, invigilators and groups: it then locks those rows
 * (rooms → users → groups, each by id, the order course sessions use) so two concurrent
 * bookings cannot both pass.
 */
class GuardExamConflictsAction
{
    public function __construct(
        private ConflictDetectorService $detector,
        private ReleaseBusyInvigilatorsAction $releaseInvigilators,
    ) {}

    /**
     * @return array<int, string> The released invigilators' names, by id.
     *
     * @throws HardConflictException
     */
    public function execute(Exam $exam): array
    {
        $slot = $exam->bookingSlot();

        Room::query()->whereKey($slot->roomIds)->orderBy('id')->lockForUpdate()->get();
        User::query()->whereKey($slot->teacherIds)->orderBy('id')->lockForUpdate()->get();
        StudentGroup::query()->whereKey($slot->groupIds)->orderBy('id')->lockForUpdate()->get();

        $released = $this->releaseInvigilators->execute($exam);
        $result = $this->detector->checkConflicts($exam->bookingSlot());

        if ($result->hasHardConflicts()) {
            throw new HardConflictException(new ConflictResult(hardConflicts: $result->hardConflicts));
        }

        return $released;
    }
}
