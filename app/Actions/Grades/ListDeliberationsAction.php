<?php

namespace App\Actions\Grades;

use App\Enums\ExamState;
use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use App\Models\ExamPeriod;
use App\Support\SchoolClock;

/**
 * The deliberation board for coordinators: a period's finished exams (normal or retake session)
 * with where each grade sheet stands, sheets waiting for a decision first.
 */
class ListDeliberationsAction
{
    /** "No sheet yet": the teacher has not opened the grid. */
    public const string NOT_STARTED = 'not_started';

    /** Board order: what waits for the coordinator first, what is settled last. */
    private const array ORDER = [
        GradeSheetStatus::Submitted->value,
        GradeSheetStatus::Draft->value,
        self::NOT_STARTED,
        GradeSheetStatus::Locked->value,
    ];

    /**
     * @return array{
     *     periods: list<array{id: int, name: string, academic_year: string, session_type: string}>,
     *     period_id: int|null,
     *     stats: array<string, int>,
     *     sheets: list<array{exam_id: int, module: string, teacher: string|null, start: string, status: string, lines: int, class_average: string|null, pass_rate: string|null}>
     * }
     */
    public function execute(?int $periodId, ?string $status): array
    {
        $periods = ExamPeriod::query()
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'academic_year', 'session_type']);
        $options = array_values($periods->map(fn (ExamPeriod $option): array => $option->toOption())->all());
        $period = $periods->firstWhere('id', $periodId) ?? $periods->first();
        $stats = array_fill_keys(self::ORDER, 0);

        if ($period === null) {
            return ['periods' => $options, 'period_id' => null, 'stats' => $stats, 'sheets' => []];
        }

        $exams = Exam::query()
            ->where('exam_period_id', $period->id)
            ->whereIn('state', ExamState::finished())
            ->with(['module:id,code,name,teacher_id', 'module.teacher:id,name', 'deliberation'])
            ->withCount('grades')
            ->orderBy('starts_at')
            ->get();

        foreach ($exams as $exam) {
            $stats[$this->statusOf($exam)]++;
        }

        $sheets = $exams
            ->filter(fn (Exam $exam): bool => $status === null || $this->statusOf($exam) === $status)
            ->sortBy(fn (Exam $exam): int => (int) array_search($this->statusOf($exam), self::ORDER, true))
            ->map(fn (Exam $exam): array => [
                'exam_id' => $exam->id,
                'module' => $exam->module->label(),
                'teacher' => $exam->module->teacher?->name,
                'start' => $exam->starts_at->format(SchoolClock::WALL_CLOCK_FORMAT),
                'status' => $this->statusOf($exam),
                'lines' => (int) $exam->getAttribute('grades_count'),
                'class_average' => $exam->deliberation?->class_average,
                'pass_rate' => $exam->deliberation?->pass_rate,
            ]);

        return [
            'periods' => $options,
            'period_id' => $period->id,
            'stats' => $stats,
            'sheets' => array_values($sheets->all()),
        ];
    }

    private function statusOf(Exam $exam): string
    {
        return $exam->deliberation->status->value ?? self::NOT_STARTED;
    }
}
