<?php

namespace App\Actions\Grades;

use App\Models\Exam;
use App\Models\ExamGrade;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves the lines a teacher changed on a draft grade sheet, with their final grades computed
 * from the module's current weighting. An absent student's exam grade is cleared.
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
            $now = now();

            ExamGrade::query()->upsert(
                array_map(function (array $line) use ($exam, $weight, $now): array {
                    $examGrade = $line['is_absent'] ? null : $line['exam_grade'];

                    return [
                        'exam_id' => $exam->id,
                        'student_id' => $line['student_id'],
                        'continuous_assessment_grade' => $line['continuous_assessment_grade'],
                        'exam_grade' => $examGrade,
                        'final_grade' => $this->calculate->execute($line['continuous_assessment_grade'], $examGrade, $line['is_absent'], $weight),
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
