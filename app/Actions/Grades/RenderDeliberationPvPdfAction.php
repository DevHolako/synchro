<?php

namespace App\Actions\Grades;

use App\Models\ExamDeliberation;
use App\Models\ExamGrade;
use App\Support\SchoolClock;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * The official deliberation PV (procès-verbal, in French): the exam, the weighting it was
 * decided with, every line with its result, the figures, who submitted and the coordinator's
 * signature block.
 */
class RenderDeliberationPvPdfAction
{
    public function __construct(
        private ListGradeLinesAction $listLines,
        private CalculateDeliberationStatsAction $stats,
    ) {}

    /**
     * @return string The PDF document.
     */
    public function execute(ExamDeliberation $deliberation): string
    {
        $deliberation->loadMissing(['exam.module.teacher:id,name', 'exam.examPeriod', 'submitter:id,name', 'locker:id,name']);
        $exam = $deliberation->exam;
        $lines = $this->listLines->execute($exam);
        $timezone = (string) config('app.schedule_timezone');
        $weight = (int) $deliberation->continuous_assessment_weight;

        return Pdf::loadView('pdf.deliberation-pv', [
            'exam' => $exam,
            'day' => $exam->starts_at->settings(['locale' => 'fr'])->isoFormat('dddd D MMMM YYYY'),
            'weight' => $weight,
            'lines' => array_map(fn (array $line): array => [
                ...$line,
                'passed' => $line['final_grade'] !== null && (float) $line['final_grade'] >= (float) ExamGrade::PASS_MARK,
            ], $lines),
            'stats' => $this->stats->execute($lines),
            'submittedBy' => $deliberation->submitter?->name,
            'submittedAt' => $deliberation->submitted_at?->copy()->setTimezone($timezone)->format('d/m/Y H:i'),
            'lockedBy' => $deliberation->locker?->name,
            'lockedAt' => $deliberation->locked_at?->copy()->setTimezone($timezone)->format('d/m/Y H:i'),
            'generatedAt' => SchoolClock::now()->format('d/m/Y H:i'),
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true)->output();
    }
}
