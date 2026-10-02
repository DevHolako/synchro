<?php

namespace App\Actions\Grades;

use App\Enums\GradeSheetStatus;
use App\Models\ExamGrade;
use App\Models\User;
use App\Support\SchoolClock;

/**
 * A student's published grades: only lines of locked deliberations, so provisional marks are
 * never shown (ADR 0006). Latest exams first, with the weighting they were decided with.
 */
class ListOwnGradesAction
{
    /**
     * @return list<array{exam_id: int, module: string, period: string, academic_year: string, session_type: string, start: string, continuous_assessment_weight: int, continuous_assessment_grade: string|null, exam_grade: string|null, is_absent: bool, final_grade: string|null, passed: bool}>
     */
    public function execute(User $student): array
    {
        $grades = ExamGrade::query()
            ->where('student_id', $student->id)
            ->whereHas('exam.deliberation', fn ($sheets) => $sheets->where('status', GradeSheetStatus::Locked))
            ->with(['exam:id,exam_period_id,module_id,starts_at', 'exam.module:id,code,name', 'exam.examPeriod:id,name,academic_year,session_type', 'exam.deliberation:id,exam_id,continuous_assessment_weight'])
            ->get()
            ->sortByDesc(fn (ExamGrade $grade) => $grade->exam->starts_at);

        return array_values($grades->map(fn (ExamGrade $grade): array => [
            'exam_id' => $grade->exam_id,
            'module' => $grade->exam->module->label(),
            'period' => $grade->exam->examPeriod->name,
            'academic_year' => $grade->exam->examPeriod->academic_year,
            'session_type' => $grade->exam->examPeriod->session_type->value,
            'start' => $grade->exam->starts_at->format(SchoolClock::WALL_CLOCK_FORMAT),
            'continuous_assessment_weight' => (int) $grade->exam->deliberation?->continuous_assessment_weight,
            'continuous_assessment_grade' => $grade->continuous_assessment_grade,
            'exam_grade' => $grade->exam_grade,
            'is_absent' => $grade->is_absent,
            'final_grade' => $grade->final_grade,
            'passed' => $grade->final_grade !== null && (float) $grade->final_grade >= (float) ExamGrade::PASS_MARK,
        ])->all());
    }
}
