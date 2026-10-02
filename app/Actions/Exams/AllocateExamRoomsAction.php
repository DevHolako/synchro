<?php

namespace App\Actions\Exams;

use App\Actions\CourseSessions\RecordConflictOverridesAction;
use App\Enums\ConflictType;
use App\Enums\Permission;
use App\Exceptions\HardConflictException;
use App\Exceptions\SoftConflictException;
use App\Models\Exam;
use App\Models\Room;
use App\Services\Scheduling\Conflict;
use App\Services\Scheduling\ConflictResult;
use App\Services\Scheduling\SoftConflictOverride;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Chooses an exam's rooms, in order, and seats its candidates across them.
 *
 * Rooms kept from the previous choice keep their invigilators. "Force Single Room" seats
 * everyone in one room whatever its exam capacity: a soft conflict (ADR 0002) that needs the
 * override permission and a justification, audited on every save.
 */
class AllocateExamRoomsAction
{
    public function __construct(
        private SyncExamRoomsAction $syncRooms,
        private ResplitExamAction $resplit,
        private GuardExamConflictsAction $guardConflicts,
        private RecordConflictOverridesAction $recordOverrides,
    ) {}

    /**
     * @param  list<int>  $roomIds  In the coordinator's order.
     * @param  SoftConflictOverride|null  $forceSingleRoom  The justified override, to seat everyone in the one room given.
     *
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws SoftConflictException
     * @throws HardConflictException
     */
    public function execute(Exam $exam, array $roomIds, ?SoftConflictOverride $forceSingleRoom = null): Exam
    {
        if (! $exam->state->isEditable()) {
            throw ValidationException::withMessages(['exam' => __('messages.exam_locked')]);
        }

        if ($forceSingleRoom !== null) {
            if (! $forceSingleRoom->user->hasPermission(Permission::OverrideSoftConflicts)) {
                throw new AuthorizationException;
            }

            if (count($roomIds) !== 1) {
                throw ValidationException::withMessages(['room_ids' => __('messages.exam_force_single_room_one')]);
            }
        }

        return DB::transaction(function () use ($exam, $roomIds, $forceSingleRoom): Exam {
            $this->syncRooms->execute($exam, $roomIds);

            $exam->update(['force_single_room' => $forceSingleRoom !== null]);
            $seated = $this->resplit->execute($exam);

            if ($exam->state->occupiesResources()) {
                $this->guardConflicts->execute($exam);
            }

            if ($forceSingleRoom !== null) {
                $this->recordOverrides->execute($exam, $this->forcedResult($exam, $roomIds[0], $seated), $forceSingleRoom);
            }

            $exam->touch();

            return $exam;
        });
    }

    /**
     * The forced single room as the soft conflict it overrides.
     */
    private function forcedResult(Exam $exam, int $roomId, int $seated): ConflictResult
    {
        $room = Room::query()->findOrFail($roomId);

        return new ConflictResult(softConflicts: [new Conflict(
            type: ConflictType::ForcedSingleRoom,
            resourceId: $room->id,
            resourceName: $room->name,
            bookingType: null,
            bookingId: null,
            startsAt: $exam->starts_at->toImmutable(),
            endsAt: $exam->ends_at->toImmutable(),
            details: ['capacity' => $room->exam_capacity, 'headcount' => $seated],
        )]);
    }
}
