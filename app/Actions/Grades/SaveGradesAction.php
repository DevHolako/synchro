<?php

namespace App\Actions\Grades;

use App\Models\Exam;
use App\Models\ExamGrade;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves the lines a teacher changed on a draft grade sheet, with their final grades computed
 * from the module's current weighting. An absent student's exam grade is cleared. On a retake,
 * the carried-over CC stays as it was and the better final is kept.
 */
class SaveGradesAction
{
    public function __construct(
        private LockDraftGradeSheetAction $lockDraft,
        private CalculateFinalGradeAction $calculate,
    ) {}

    /**
     * @param  list<array{student_id: int, continuous_assessment_grade: string|null, exam_grade: string|null, is_absent: bool, remarks: string|null}>  $lines  Lines already on the sheet.
     *
     * @throws ValidationException
     */
    public function execute(Exam $exam, array $lines): void
    {
        if ($lines === []) {
            return;
        }

        DB::transaction(function () use ($exam, $lines): void {
            $this->lockDraft->execute($exam);

            $weight = (int) $exam->module()->value('continuous_assessment_weight');
            $stored = $exam->isRetake()
                ? $exam->grades()->whereIn('student_id', array_column($lines, 'student_id'))->get()->keyBy('student_id')
                : null;
            $now = now();

            ExamGrade::query()->upsert(
                array_map(function (array $line) use ($exam, $weight, $stored, $now): array {
                    $examGrade = $line['is_absent'] ? null : $line['exam_grade'];
                    $storedLine = $stored?->get($line['student_id']);
                    $continuousAssessment = $stored === null ? $line['continuous_assessment_grade'] : $storedLine?->continuous_assessment_grade;

                    return [
                        'exam_id' => $exam->id,
                        'student_id' => $line['student_id'],
                        'continuous_assessment_grade' => $continuousAssessment,
                        'exam_grade' => $examGrade,
                        'final_grade' => $this->calculate->execute($continuousAssessment, $examGrade, $line['is_absent'], $weight, $storedLine?->previous_final_grade),
                        'is_absent' => $line['is_absent'],
                        'remarks' => $line['remarks'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }, $lines),
                ['exam_id', 'student_id'],
                ['continuous_assessment_grade', 'exam_grade', 'final_grade', 'is_absent', 'remarks', 'updated_at'],
            );
        });
    }
}
