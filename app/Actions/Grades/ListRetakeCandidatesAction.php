<?php

namespace App\Actions\Grades;

use App\Enums\ExamSessionType;
use App\Enums\GradeSheetStatus;
use App\Models\ExamGrade;
use App\Support\GradeScale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Who must sit the retake session (spec 05 / ticket 04): students whose locked final grade in a
 * normal session of the academic year is below the pass mark, per module. When a module was
 * examined twice, the latest locked line decides (the later exam, then the later sheet on a
 * tie), so only students who failed somewhere are loaded, with all their locked lines. Absent
 * students are among them when their final is below the pass mark too.
 */
class ListRetakeCandidatesAction
{
    /**
     * @param  list<int>|null  $moduleIds  Null for every module.
     * @return Collection<int, ExamGrade> Each candidate's deciding line, with its exam's `module_id`.
     */
    public function execute(string $academicYear, ?array $moduleIds = null): Collection
    {
        $lockedLines = fn (): Builder => ExamGrade::query()->whereHas('exam', fn (Builder $exams) => $exams
            ->when($moduleIds !== null, fn (Builder $query) => $query->whereIn('module_id', $moduleIds))
            ->whereHas('examPeriod', fn (Builder $periods) => $periods
                ->where('session_type', ExamSessionType::Normal)
                ->where('academic_year', $academicYear))
            ->whereHas('deliberation', fn (Builder $sheets) => $sheets->where('status', GradeSheetStatus::Locked)));

        return $lockedLines()
            ->whereIn('student_id', $lockedLines()->where('final_grade', '<', GradeScale::PASS_MARK)->select('student_id'))
            ->with('exam:id,module_id,starts_at')
            ->get()
            ->sort(fn (ExamGrade $a, ExamGrade $b): int => [$b->exam->starts_at->getTimestamp(), $b->exam_id] <=> [$a->exam->starts_at->getTimestamp(), $a->exam_id])
            ->unique(fn (ExamGrade $line): string => $line->exam->module_id.'-'.$line->student_id)
            ->filter(fn (ExamGrade $line): bool => $line->final_grade !== null && ! GradeScale::passes($line->final_grade))
            ->values();
    }
}
