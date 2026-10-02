<?php

namespace App\Actions\Grades;

use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use App\Models\ExamDeliberation;
use App\Models\User;
use App\Support\SchoolClock;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

/**
 * An exam's grade sheet for the grading grid: the module's weighting (on a locked sheet, the one
 * it was deliberated with), the sheet's status, one line per candidate in official order, and the
 * deliberation (figures, send-back, lock, PV).
 */
class ShowGradeSheetAction
{
    public function __construct(
        private ListGradeLinesAction $listLines,
        private CalculateDeliberationStatsAction $stats,
    ) {}

    /**
     * @return array{
     *     exam: array{id: int, module: string, period: string, start: string, end: string, retake: bool},
     *     weights: array{continuous_assessment: int, exam: int},
     *     sheet: array{status: string, submitted_at: string|null, submitted_by: string|null},
     *     can_edit: bool,
     *     rows: list<array{student_id: int, name: string, student_number: string|null, group: string|null, continuous_assessment_grade: string|null, exam_grade: string|null, final_grade: string|null, previous_final_grade: string|null, is_absent: bool, remarks: string|null}>,
     *     deliberation: array{
     *         stats: array{graded: int, average: string|null, median: string|null, pass_rate: string|null, passing: int, failing: int, absent: int},
     *         returned_at: string|null,
     *         return_reason: string|null,
     *         locked_at: string|null,
     *         locked_by: string|null,
     *         pv: 'ready'|'pending'|null,
     *         can_decide: bool
     *     }
     * }
     */
    public function execute(Exam $exam, ExamDeliberation $sheet, User $viewer): array
    {
        $exam->loadMissing(['module', 'examPeriod']);
        $sheet->loadMissing([
            'submitter:id,name', 'submitter.studentProfile:id,user_id,last_name,first_name',
            'locker:id,name', 'locker.studentProfile:id,user_id,last_name,first_name',
        ]);
        $lines = $this->listLines->execute($exam);
        $locked = $sheet->status === GradeSheetStatus::Locked;
        $weight = $locked ? (int) $sheet->continuous_assessment_weight : $exam->module->continuous_assessment_weight;

        return [
            'exam' => [
                'id' => $exam->id,
                'module' => $exam->module->label(),
                'period' => $exam->examPeriod->name,
                'start' => $exam->starts_at->format(SchoolClock::WALL_CLOCK_FORMAT),
                'end' => $exam->ends_at->format(SchoolClock::WALL_CLOCK_FORMAT),
                'retake' => $exam->isRetake(),
            ],
            'weights' => [
                'continuous_assessment' => $weight,
                'exam' => 100 - $weight,
            ],
            'sheet' => [
                'status' => $sheet->status->value,
                'submitted_at' => $this->wallClock($sheet->submitted_at),
                'submitted_by' => $sheet->submitter?->officialName(),
            ],
            'can_edit' => $sheet->status === GradeSheetStatus::Draft && $viewer->can('enterGrades', $exam),
            'rows' => $lines,
            'deliberation' => [
                'stats' => $this->stats->execute($lines),
                'returned_at' => $this->wallClock($sheet->returned_at),
                'return_reason' => $sheet->return_reason,
                'locked_at' => $this->wallClock($sheet->locked_at),
                'locked_by' => $sheet->locker?->officialName(),
                'pv' => $locked && $viewer->can('downloadPv', $exam)
                    ? (Storage::disk('local')->exists($sheet->pvPath()) ? 'ready' : 'pending')
                    : null,
                'can_decide' => $sheet->status === GradeSheetStatus::Submitted && $viewer->can('lockGrades', $exam),
            ],
        ];
    }

    private function wallClock(?CarbonInterface $moment): ?string
    {
        return $moment?->copy()->setTimezone((string) config('app.schedule_timezone'))->format(SchoolClock::WALL_CLOCK_FORMAT);
    }
}
