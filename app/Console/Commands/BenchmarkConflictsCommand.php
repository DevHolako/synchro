<?php

namespace App\Console\Commands;

use App\Models\Module;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;
use App\Services\Scheduling\ConflictDetectorService;
use App\Services\Scheduling\SessionSlot;
use Carbon\CarbonImmutable;
use Faker\Factory as FakerFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Times conflict checks against a seeded timetable (target: well under 80 ms per check).
 *
 * Everything runs inside a transaction that is rolled back, so no data is kept. Needs the
 * dev dependencies (model factories), so it is a local and CI tool, not a production one.
 */
class BenchmarkConflictsCommand extends Command
{
    protected $signature = 'conflicts:benchmark
        {--sessions=5000 : Course sessions to seed}
        {--checks=200 : Conflict checks to time}';

    protected $description = 'Time ConflictDetectorService::checkConflicts() on a seeded, rolled-back timetable';

    private const int ROOMS = 40;

    private const int TEACHERS = 80;

    private const int GROUPS = 60;

    private const int WEEKS = 26;

    public function handle(ConflictDetectorService $detector): int
    {
        if (! class_exists(FakerFactory::class)) {
            $this->error('This benchmark needs the dev dependencies (fakerphp/faker for model factories).');

            return self::FAILURE;
        }

        $sessions = max(1, (int) $this->option('sessions'));
        $checks = max(1, (int) $this->option('checks'));

        DB::beginTransaction();

        try {
            [$roomIds, $teacherIds, $groupIds] = $this->seedTimetable($sessions);

            $timings = [];

            for ($i = 0; $i < $checks; $i++) {
                $slot = $this->randomSlot($roomIds, $teacherIds, $groupIds);

                $started = hrtime(true);
                $detector->checkConflicts($slot);
                $timings[] = (hrtime(true) - $started) / 1e6;
            }
        } finally {
            DB::rollBack();
        }

        sort($timings);

        $this->table(['Sessions', 'Checks', 'Average (ms)', 'p95 (ms)', 'Max (ms)'], [[
            $sessions,
            $checks,
            number_format(array_sum($timings) / count($timings), 2),
            number_format($timings[(int) floor(0.95 * (count($timings) - 1))], 2),
            number_format(end($timings), 2),
        ]]);

        return self::SUCCESS;
    }

    /**
     * @return array{list<int>, list<int>, list<int>}
     */
    private function seedTimetable(int $sessions): array
    {
        $module = Module::factory()->create();
        $roomIds = Room::factory()->count(self::ROOMS)->create()->modelKeys();
        $teacherIds = User::factory()->teacher()->count(self::TEACHERS)->create()->modelKeys();
        $groupIds = StudentGroup::factory()->count(self::GROUPS)->create(['program_id' => $module->program_id])->modelKeys();

        $now = now();
        $nextId = (int) DB::table('course_sessions')->max('id') + 1;

        foreach (array_chunk(range(1, $sessions), 500) as $chunk) {
            $rows = [];
            $links = [];

            foreach ($chunk as $_) {
                $slot = $this->randomSlot($roomIds, $teacherIds, $groupIds);
                $rows[] = [
                    'id' => $nextId,
                    'module_id' => $module->id,
                    'teacher_id' => $slot->teacherId,
                    'room_id' => $slot->roomId,
                    'starts_at' => $slot->startsAt,
                    'ends_at' => $slot->endsAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                foreach ($slot->groupIds as $groupId) {
                    $links[] = ['course_session_id' => $nextId, 'student_group_id' => $groupId];
                }

                $nextId++;
            }

            DB::table('course_sessions')->insert($rows);
            DB::table('course_session_student_group')->insert($links);
        }

        return [array_values($roomIds), array_values($teacherIds), array_values($groupIds)];
    }

    /**
     * A two-hour slot on a random weekday of the coming weeks, inside the grid, on a quarter hour.
     *
     * @param  array<int, int>  $roomIds
     * @param  array<int, int>  $teacherIds
     * @param  array<int, int>  $groupIds
     */
    private function randomSlot(array $roomIds, array $teacherIds, array $groupIds): SessionSlot
    {
        $start = CarbonImmutable::parse('next monday 08:00')
            ->addWeeks(random_int(0, self::WEEKS - 1))
            ->addDays(random_int(0, 5))
            ->addMinutes(15 * random_int(0, 48));

        return new SessionSlot(
            teacherId: $teacherIds[array_rand($teacherIds)],
            roomId: $roomIds[array_rand($roomIds)],
            groupIds: array_values(array_unique([$groupIds[array_rand($groupIds)], $groupIds[array_rand($groupIds)]])),
            startsAt: $start,
            endsAt: $start->addHours(2),
        );
    }
}
