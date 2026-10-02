<?php

namespace App\Actions\Exams;

use App\Actions\CourseSessions\RecordConflictOverridesAction;
use App\Enums\InvigilatorRole;
use App\Enums\Permission;
use App\Exceptions\HardConflictException;
use App\Exceptions\SoftConflictException;
use App\Models\Exam;
use App\Models\ExamInvigilator;
use App\Models\ExamRoomAssignment;
use App\Models\User;
use App\Services\Scheduling\ConflictDetectorService;
use App\Services\Scheduling\SoftConflictOverride;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Staffs one exam room: one lead invigilator and any number of assistants.
 *
 * Invigilators may change until the exam starts, even once it is published: replacing one does
 * not touch any student's convocation. Once the exam books its resources, an invigilator busy
 * elsewhere is a hard conflict; a declared unavailability is always a soft one, to override
 * with a justification.
 */
class AssignInvigilatorsAction
{
    public function __construct(
        private ConflictDetectorService $detector,
        private RecordConflictOverridesAction $recordOverrides,
    ) {}

    /**
     * @param  list<int>  $assistantIds
     *
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws HardConflictException
     * @throws SoftConflictException
     */
    public function execute(ExamRoomAssignment $room, int $leadId, array $assistantIds, ?SoftConflictOverride $override = null): ExamRoomAssignment
    {
        $exam = $room->exam;

        if ($exam->state->isFinished() || $exam->hasStarted()) {
            throw ValidationException::withMessages(['exam' => __('messages.exam_invigilators_locked')]);
        }

        if ($override !== null && ! $override->user->hasPermission(Permission::OverrideSoftConflicts)) {
            throw new AuthorizationException;
        }

        $teacherIds = [$leadId, ...$assistantIds];

        return DB::transaction(function () use ($exam, $room, $leadId, $teacherIds, $override): ExamRoomAssignment {
            $exam->lockRow();
            User::query()->whereKey($teacherIds)->orderBy('id')->lockForUpdate()->get();

            $elsewhere = ExamInvigilator::query()
                ->where('exam_id', $exam->id)
                ->where('exam_room_assignment_id', '!=', $room->id)
                ->whereIn('teacher_id', $teacherIds)
                ->with('teacher:id,name')
                ->first();

            if ($elsewhere !== null) {
                throw ValidationException::withMessages(['assistant_ids' => __('messages.exam_invigilator_elsewhere', ['name' => $elsewhere->teacher->name])]);
            }

            $result = $this->detector->checkConflicts($exam->invigilationSlot($teacherIds));

            if ($exam->state->occupiesResources() && $result->hasHardConflicts()) {
                throw new HardConflictException($result);
            }

            if ($result->hasSoftConflicts() && $override === null) {
                throw new SoftConflictException($result);
            }

            $room->invigilators()->delete();
            $room->invigilators()->createMany(array_map(fn (int $teacherId): array => [
                'exam_id' => $exam->id,
                'teacher_id' => $teacherId,
                'role' => $teacherId === $leadId ? InvigilatorRole::Principal : InvigilatorRole::Adjoint,
            ], $teacherIds));

            if ($override !== null && $result->hasSoftConflicts()) {
                $this->recordOverrides->execute($exam, $result, $override);
            }

            $exam->touch();

            return $room;
        });
    }
}
