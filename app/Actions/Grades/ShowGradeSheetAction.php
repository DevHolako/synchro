<?php

namespace App\Actions\Grades;

use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use App\Models\ExamDeliberation;
use App\Models\ExamGrade;
use App\Models\User;
use App\Support\SchoolClock;
use Collator;

/**
 * An exam's grade sheet for the grading grid: the module's weighting, the sheet's status, and
 * one line per candidate in official alphabetical order (French collation).
 */
class ShowGradeSheetAction
{
    /**
     * @return array{
     *     exam: array{id: int, module: string, period: string, start: string, end: string},
     *     weights: array{continuous_assessment: int, exam: int},
     *     sheet: array{status: string, submitted_at: string|null, submitted_by: string|null},
     *     can_edit: bool,
     *     rows: list<array{student_id: int, name: string, student_number: string|null, group: string|null, continuous_assessment_grade: string|null, exam_grade: string|null, final_grade: string|null, is_absent: bool, remarks: string|null}>
     * }
     */
    public function execute(Exam $exam, ExamDeliberation $sheet, User $viewer): array
    {
        $exam->loadMissing(['module', 'examPeriod:id,name']);
        $sheet->loadMissing('submitter:id,name');

        $grades = $exam->grades()
            ->with(['student:id,name', 'student.studentProfile:id,user_id,student_group_id,last_name,first_name,student_number', 'student.studentProfile.studentGroup:id,name'])
            ->get();

        return [
            'exam' => [
                'id' => $exam->id,
                'module' => $exam->module->label(),
                'period' => $exam->examPeriod->name,
                'start' => $exam->starts_at->format(SchoolClock::WALL_CLOCK_FORMAT),
                'end' => $exam->ends_at->format(SchoolClock::WALL_CLOCK_FORMAT),
            ],
            'weights' => [
                'continuous_assessment' => $exam->module->continuous_assessment_weight,
                'exam' => $exam->module->exam_weight,
            ],
            'sheet' => [
                'status' => $sheet->status->value,
                'submitted_at' => $sheet->submitted_at?->copy()->setTimezone((string) config('app.schedule_timezone'))->format(SchoolClock::WALL_CLOCK_FORMAT),
                'submitted_by' => $sheet->submitter?->name,
            ],
            'can_edit' => $sheet->status === GradeSheetStatus::Draft && $viewer->can('enterGrades', $exam),
            'rows' => $this->sorted(array_values($grades->map(fn (ExamGrade $grade): array => [
                'student_id' => $grade->student_id,
                'name' => $grade->student->officialName(),
                'student_number' => $grade->student->studentProfile?->student_number,
                'group' => $grade->student->studentProfile?->studentGroup?->name,
                'continuous_assessment_grade' => $grade->continuous_assessment_grade,
                'exam_grade' => $grade->exam_grade,
                'final_grade' => $grade->final_grade,
                'is_absent' => $grade->is_absent,
                'remarks' => $grade->remarks,
            ])->all())),
        ];
    }

    /**
     * @template TRow of array{name: string, student_number: string|null}
     *
     * @param  list<TRow>  $rows
     * @return list<TRow>
     */
    private function sorted(array $rows): array
    {
        $collator = new Collator('fr_FR');
        $collator->setStrength(Collator::PRIMARY);

        usort($rows, fn (array $a, array $b): int => $collator->compare($a['name'], $b['name'])
            ?: strcmp((string) $a['student_number'], (string) $b['student_number']));

        return $rows;
    }
}
