<?php

namespace App\Actions\CourseSessions;

use App\Enums\Permission;
use App\Exceptions\HardConflictException;
use App\Exceptions\SoftConflictException;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;
use App\Services\Scheduling\ConflictDetectorService;
use App\Services\Scheduling\ConflictResult;
use App\Services\Scheduling\SessionSlot;
use App\Services\Scheduling\SoftConflictOverride;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Lets a slot through only when it has no hard conflict, and no soft conflict unless overridden.
 *
 * Must run inside the caller's transaction: it first locks the slot's room, teacher and
 * group rows (always rooms → users → groups, each by id, so concurrent bookings cannot
 * deadlock) so two concurrent bookings of the same resource cannot both pass.
 */
class GuardSessionConflictsAction
{
    public function __construct(private ConflictDetectorService $detector) {}

    /**
     * @return ConflictResult The soft conflicts the caller must audit when an override was given.
     *
     * @throws HardConflictException
     * @throws SoftConflictException
     * @throws AuthorizationException
     */
    public function execute(SessionSlot $slot, ?SoftConflictOverride $override = null): ConflictResult
    {
        if ($override !== null && ! $override->user->hasPermission(Permission::OverrideSoftConflicts)) {
            throw new AuthorizationException;
        }

        Room::query()->whereKey($slot->roomId)->lockForUpdate()->first();
        User::query()->whereKey($slot->teacherId)->lockForUpdate()->first();
        StudentGroup::query()->whereKey($slot->groupIds)->orderBy('id')->lockForUpdate()->get();

        $result = $this->detector->checkConflicts($slot);

        if ($result->hasHardConflicts()) {
            throw new HardConflictException($result);
        }

        if ($result->hasSoftConflicts() && $override === null) {
            throw new SoftConflictException($result);
        }

        return $result;
    }
}
