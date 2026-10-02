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
 * Lets a booked exam through only when none of its rooms, invigilators or groups is taken.
 *
 * Must run inside the caller's transaction, after the exam's rooms, invigilators and groups are
 * written: it locks those rows (rooms → users → groups, each by id, the order course sessions
 * use) so two concurrent bookings cannot both pass. Invigilators' declared unavailabilities are
 * settled when they are assigned, so only hard conflicts stop the exam here.
 */
class GuardExamConflictsAction
{
    public function __construct(private ConflictDetectorService $detector) {}

    /**
     * @throws HardConflictException
     */
    public function execute(Exam $exam): ConflictResult
    {
        $slot = $exam->bookingSlot();

        Room::query()->whereKey($slot->roomIds)->orderBy('id')->lockForUpdate()->get();
        User::query()->whereKey($slot->teacherIds)->orderBy('id')->lockForUpdate()->get();
        StudentGroup::query()->whereKey($slot->groupIds)->orderBy('id')->lockForUpdate()->get();

        $result = $this->detector->checkConflicts($slot);

        if ($result->hasHardConflicts()) {
            throw new HardConflictException(new ConflictResult(hardConflicts: $result->hardConflicts));
        }

        return $result;
    }
}
