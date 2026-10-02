<?php

namespace App\Actions\Grades;

use App\Models\Exam;
use App\Models\ExamGrade;
use Collator;

/**
 * An exam's grade lines in official alphabetical order (French collation), with each student's
 * official name, matricule and group: for the grading grid and the PV alike.
 */
class ListGradeLinesAction
{
    /**
     * @return list<array{student_id: int, name: string, student_number: string|null, group: string|null, continuous_assessment_grade: string|null, exam_grade: string|null, final_grade: string|null, previous_final_grade: string|null, is_absent: bool, remarks: string|null}>
     */
    public function execute(Exam $exam): array
    {
        $lines = $exam->grades()
            ->with(['student:id,name', 'student.studentProfile:id,user_id,student_group_id,last_name,first_name,student_number', 'student.studentProfile.studentGroup:id,name'])
            ->get()
            ->map(fn (ExamGrade $grade): array => [
                'student_id' => $grade->student_id,
                'name' => $grade->student->officialName(),
                'student_number' => $grade->student->studentProfile?->student_number,
                'group' => $grade->student->studentProfile?->studentGroup?->name,
                'continuous_assessment_grade' => $grade->continuous_assessment_grade,
                'exam_grade' => $grade->exam_grade,
                'final_grade' => $grade->final_grade,
                'previous_final_grade' => $grade->previous_final_grade,
                'is_absent' => $grade->is_absent,
                'remarks' => $grade->remarks,
            ])
            ->all();

        $collator = new Collator('fr_FR');
        $collator->setStrength(Collator::PRIMARY);

        usort($lines, fn (array $a, array $b): int => $collator->compare($a['name'], $b['name'])
            ?: strcmp((string) $a['student_number'], (string) $b['student_number']));

        return $lines;
    }
}
