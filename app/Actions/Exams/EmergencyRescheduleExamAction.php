<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Exceptions\HardConflictException;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamPeriod;
use App\Models\ExamReschedule;
use App\Models\SupersededConvocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Moves a published exam in an emergency (ADR 0005): new time and optionally new rooms, an
 * audited reason, a new revision. Every convocation issued so far is superseded (its QR code
 * now shows a warning) and replaced, the documents are generated again, and everyone concerned
 * is alerted. Invigilators busy or unavailable at the new time, or watching a room no longer
 * used, are released, to be replaced in the sheet.
 */
class EmergencyRescheduleExamAction
{
    public function __construct(
        private EnsureExamTimesFitAction $ensureTimesFit,
        private SyncExamRoomsAction $syncRooms,
        private ResplitExamAction $resplit,
        private GuardExamConflictsAction $guardConflicts,
        private QueueExamDocumentsAction $queueDocuments,
        private NotifyExamRescheduledAction $notify,
    ) {}

    /**
     * @param  array{starts_at: string, ends_at: string, room_ids: list<int>|null}  $data  Null rooms keep the current ones.
     * @return array{exam: Exam, released: array<int, string>} The exam, and the released invigilators' names, by id.
     *
     * @throws ValidationException
     * @throws HardConflictException
     */
    public function execute(Exam $exam, array $data, string $reason, User $user): array
    {
        if ($exam->state !== ExamState::Published || $exam->hasStarted()) {
            throw ValidationException::withMessages(['exam' => __('messages.exam_reschedule_not_allowed')]);
        }

        return DB::transaction(function () use ($exam, $data, $reason, $user): array {
            $period = ExamPeriod::query()->whereKey($exam->exam_period_id)->lockForUpdate()->firstOrFail();
            $exam->lockRow();
            $this->ensureTimesFit->execute($period, $data['starts_at']);

            $previousStart = $exam->starts_at->format('Y-m-d H:i:s');
            $previousEnd = $exam->ends_at->format('Y-m-d H:i:s');
            $previousRoomIds = array_values(array_map('intval', $exam->roomAssignments()->pluck('room_id')->all()));
            $oldFiles = [$exam->rosterPath(), ...$exam->candidates()->get()->map(fn (ExamCandidate $candidate) => $candidate->convocationPath())->all()];

            $this->supersedeConvocations($exam);

            $exam->fill(['starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at']])->save();

            $dropped = [];

            if ($data['room_ids'] !== null && $data['room_ids'] !== $previousRoomIds) {
                // Invigilators of rooms no longer used go with those rooms: they are released too.
                $dropped = User::query()
                    ->whereIn('id', $exam->invigilators()
                        ->whereHas('roomAssignment', fn ($rooms) => $rooms->whereNotIn('room_id', $data['room_ids']))
                        ->select('teacher_id'))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all();
                $this->syncRooms->execute($exam, $data['room_ids']);
                $exam->update(['force_single_room' => false]);
                $this->resplit->execute($exam);
            } else {
                $exam->candidates()->get()->each(fn (ExamCandidate $candidate) => $candidate->update([
                    'convocation_uuid' => (string) Str::uuid(),
                    'checked_in_at' => null,
                    'checked_in_by' => null,
                ]));
            }

            $busy = $this->guardConflicts->execute($exam);
            $released = $dropped + $busy;
            $releasedIds = array_keys($released);

            $exam->update(['revision' => $exam->revision + 1]);

            ExamReschedule::create([
                'exam_id' => $exam->id,
                'user_id' => $user->id,
                'revision' => $exam->revision,
                'reason' => $reason,
                'previous_starts_at' => $previousStart,
                'previous_ends_at' => $previousEnd,
                'starts_at' => $exam->starts_at->format('Y-m-d H:i:s'),
                'ends_at' => $exam->ends_at->format('Y-m-d H:i:s'),
                'previous_room_ids' => $previousRoomIds,
                'room_ids' => array_values(array_map('intval', $exam->roomAssignments()->pluck('room_id')->all())),
                'released_invigilator_ids' => $releasedIds,
            ]);

            DB::afterCommit(function () use ($oldFiles): void {
                Storage::disk('local')->delete($oldFiles);
            });
            $this->queueDocuments->execute([$exam->id]);
            DB::afterCommit(fn () => $this->notify->execute($exam, $reason, array_keys($busy), array_keys($dropped)));

            return ['exam' => $exam, 'released' => $released];
        });
    }

    /**
     * Keep every issued convocation identity, so scanning it later says it was superseded.
     */
    private function supersedeConvocations(Exam $exam): void
    {
        $exam->candidates()->get()->each(fn (ExamCandidate $candidate) => SupersededConvocation::create([
            'exam_id' => $exam->id,
            'student_id' => $candidate->student_id,
            'convocation_uuid' => $candidate->convocation_uuid,
            'revision' => $exam->revision,
        ]));
    }
}
