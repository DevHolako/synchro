<?php

namespace App\Actions\Grades;

use App\Enums\ExamSessionType;
use App\Models\Exam;
use App\Models\ExamGrade;
use App\Models\ExamPeriod;
use Collator;

/**
 * The retake roster for a retake period (spec 05 / ticket 04): per module, the students who
 * failed it in the academic year's normal sessions, by group, and the module's retake exam once
 * it exists.
 */
class ShowRetakeRosterAction
{
    public function __construct(private ListRetakeCandidatesAction $retakeCandidates) {}

    /**
     * @return array{
     *     periods: list<array{id: int, name: string, academic_year: string}>,
     *     period: array{id: int, name: string, academic_year: string, start_date: string, end_date: string}|null,
     *     modules: list<array{
     *         module_id: int,
     *         module: string,
     *         group_ids: list<int>,
     *         exam: array{id: int, state: string}|null,
     *         students: list<array{student_id: int, name: string, student_number: string|null, group: string|null, final_grade: string|null}>
     *     }>
     * }
     */
    public function execute(?int $periodId): array
    {
        $periods = ExamPeriod::query()
            ->where('session_type', ExamSessionType::Rattrapage)
            ->orderByDesc('start_date')
            ->get();
        $period = $periods->firstWhere('id', $periodId) ?? $periods->first();
        $options = array_values($periods->map(fn (ExamPeriod $option): array => [
            'id' => $option->id,
            'name' => $option->name,
            'academic_year' => $option->academic_year,
        ])->all());

        if ($period === null) {
            return ['periods' => $options, 'period' => null, 'modules' => []];
        }

        $lines = $this->retakeCandidates->execute($period->academic_year)->load([
            'exam.module:id,code,name',
            'student:id,name',
            'student.studentProfile:id,user_id,student_group_id,last_name,first_name,student_number',
            'student.studentProfile.studentGroup:id,name',
        ]);
        $retakeExams = Exam::query()
            ->where('exam_period_id', $period->id)
            ->whereIn('module_id', $lines->map(fn (ExamGrade $line): int => $line->exam->module_id)->unique()->values()->all())
            ->orderBy('id')
            ->get(['id', 'module_id', 'state'])
            ->keyBy('module_id');
        $collator = new Collator('fr_FR');
        $collator->setStrength(Collator::PRIMARY);

        $modules = $lines->groupBy(fn (ExamGrade $line): int => $line->exam->module_id)
            ->map(function ($moduleLines, int $moduleId) use ($retakeExams, $collator): array {
                $students = array_values($moduleLines->map(fn (ExamGrade $line): array => [
                    'student_id' => $line->student_id,
                    'name' => $line->student->officialName(),
                    'student_number' => $line->student->studentProfile?->student_number,
                    'group' => $line->student->studentProfile?->studentGroup?->name,
                    'final_grade' => $line->final_grade,
                ])->all());

                usort($students, fn (array $a, array $b): int => $collator->compare((string) $a['group'], (string) $b['group'])
                    ?: $collator->compare($a['name'], $b['name'])
                    ?: $a['student_id'] <=> $b['student_id']);

                $exam = $retakeExams->get($moduleId);

                return [
                    'module_id' => $moduleId,
                    'module' => $moduleLines->first()->exam->module->label(),
                    // The groups the retake exam is for: those of its failing students.
                    'group_ids' => array_values(array_map('intval', $moduleLines
                        ->map(fn (ExamGrade $line): ?int => $line->student->studentProfile?->student_group_id)
                        ->filter()
                        ->unique()
                        ->all())),
                    'exam' => $exam === null ? null : ['id' => $exam->id, 'state' => $exam->state->value],
                    'students' => $students,
                ];
            })
            ->sortBy('module', SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'periods' => $options,
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'academic_year' => $period->academic_year,
                'start_date' => $period->start_date->format('Y-m-d'),
                'end_date' => $period->end_date->format('Y-m-d'),
            ],
            'modules' => array_values($modules->all()),
        ];
    }
}
