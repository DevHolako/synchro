<?php

namespace App\Actions\CourseSessions;

use App\Models\Module;
use App\Models\StudentGroup;
use App\Services\Scheduling\ConflictDetectorService;
use App\Services\Scheduling\SessionSlot;
use Carbon\CarbonImmutable;

/**
 * Previews a batch before anything is written: each slot's conflicts with saved bookings,
 * the slots of the batch it overlaps, and how the batch moves each group's syllabus hours.
 */
class CheckCourseSessionBatchAction
{
    public function __construct(
        private ConflictDetectorService $detector,
        private SumPlannedMinutesAction $sumPlannedMinutes,
    ) {}

    /**
     * @param array{
     *     module_id: int,
     *     teacher_id: int,
     *     room_id: int,
     *     student_group_ids: list<int>,
     *     slots: list<array{starts_at: string, ends_at: string}>
     * } $data
     * @return array{
     *     slots: list<array<string, mixed>>,
     *     syllabus: array{total_hours: int, batch_minutes: int, groups: list<array{id: int, name: string, planned_minutes: int}>}
     * }
     */
    public function execute(array $data): array
    {
        $windows = array_map(fn (array $slot): array => [
            CarbonImmutable::parse($slot['starts_at']),
            CarbonImmutable::parse($slot['ends_at']),
        ], $data['slots']);

        $slots = [];

        foreach ($windows as $index => [$start, $end]) {
            $result = $this->detector->checkConflicts(new SessionSlot(
                teacherId: $data['teacher_id'],
                roomId: $data['room_id'],
                groupIds: $data['student_group_ids'],
                startsAt: $start,
                endsAt: $end,
            ));

            $slots[] = [
                'index' => $index,
                ...$data['slots'][$index],
                'overlaps' => $this->overlapsWithinBatch($windows, $index),
                ...$result->toArray(),
            ];
        }

        return [
            'slots' => $slots,
            'syllabus' => [
                'total_hours' => (int) Module::query()->whereKey($data['module_id'])->value('total_hours'),
                'batch_minutes' => array_sum(array_map(
                    fn (array $window): int => (int) $window[0]->diffInMinutes($window[1]),
                    $windows,
                )),
                'groups' => $this->plannedMinutesPerGroup($data['module_id'], $data['student_group_ids']),
            ],
        ];
    }

    /**
     * The other slots of the batch a slot overlaps: they share every resource, so any overlap collides.
     *
     * @param  list<array{0: CarbonImmutable, 1: CarbonImmutable}>  $windows
     * @return list<int>
     */
    private function overlapsWithinBatch(array $windows, int $index): array
    {
        [$start, $end] = $windows[$index];
        $overlaps = [];

        foreach ($windows as $other => [$otherStart, $otherEnd]) {
            if ($other !== $index && $otherStart < $end && $otherEnd > $start) {
                $overlaps[] = $other;
            }
        }

        return $overlaps;
    }

    /**
     * The module's hours already planned for each group.
     *
     * @param  list<int>  $groupIds
     * @return list<array{id: int, name: string, planned_minutes: int}>
     */
    private function plannedMinutesPerGroup(int $moduleId, array $groupIds): array
    {
        $planned = $this->sumPlannedMinutes->execute($groupIds, $moduleId);

        return array_values(StudentGroup::query()
            ->whereKey($groupIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (StudentGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'planned_minutes' => $planned[$group->id][$moduleId] ?? 0,
            ])
            ->all());
    }
}
