<?php

namespace App\Actions\CourseSessions;

use App\Exceptions\HardConflictException;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;
use App\Services\Scheduling\ConflictDetectorService;
use App\Services\Scheduling\SessionSlot;

/**
 * Rejects a slot that would double-book its room, teacher or groups.
 *
 * Must run inside the caller's transaction: it first locks the slot's room, teacher and
 * group rows (always rooms → users → groups, each by id, so concurrent bookings cannot
 * deadlock) so two concurrent bookings of the same resource cannot both pass.
 */
class GuardSessionConflictsAction
{
    public function __construct(private ConflictDetectorService $detector) {}

    /**
     * @throws HardConflictException
     */
    public function execute(SessionSlot $slot): void
    {
        Room::query()->whereKey($slot->roomId)->lockForUpdate()->first();
        User::query()->whereKey($slot->teacherId)->lockForUpdate()->first();
        StudentGroup::query()->whereKey($slot->groupIds)->orderBy('id')->lockForUpdate()->get();

        $result = $this->detector->checkConflicts($slot);

        if ($result->hasHardConflicts()) {
            throw new HardConflictException($result);
        }
    }
}
