<?php

namespace App\Http\Requests\Grades;

use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\Exam;
use App\Support\GradeScale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveGradesRequest extends FormRequest
{
    use ReadsTypedInput;

    /** The grade fields of a line. */
    private const array GRADE_FIELDS = ['continuous_assessment_grade', 'exam_grade'];

    public function authorize(): bool
    {
        return $this->user()?->can('enterGrades', $this->exam()) ?? false;
    }

    /**
     * Grades typed the French way ("14,5") are read as "14.5"; blank grades are missing ones.
     */
    protected function prepareForValidation(): void
    {
        $lines = $this->input('grades');

        if (! is_array($lines)) {
            return;
        }

        foreach ($lines as $index => $line) {
            foreach (self::GRADE_FIELDS as $field) {
                if (is_array($line) && is_string($line[$field] ?? null)) {
                    $value = str_replace(',', '.', trim($line[$field]));
                    $lines[$index][$field] = $value === '' ? null : $value;
                }
            }
        }

        $this->merge(['grades' => $lines]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $grade = ['present', 'nullable', 'numeric', 'decimal:0,2', 'between:0,'.GradeScale::MAX];

        return [
            'grades' => ['required', 'array'],
            'grades.*' => ['array:student_id,continuous_assessment_grade,exam_grade,is_absent,remarks'],
            'grades.*.student_id' => ['required', 'integer', 'distinct'],
            'grades.*.continuous_assessment_grade' => $grade,
            'grades.*.exam_grade' => $grade,
            'grades.*.is_absent' => ['required', 'boolean'],
            'grades.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $invalid = __('messages.grade_invalid', ['max' => GradeScale::MAX]);
        $messages = [];

        foreach (self::GRADE_FIELDS as $field) {
            foreach (['numeric', 'decimal', 'between'] as $rule) {
                $messages["grades.*.{$field}.{$rule}"] = $invalid;
            }
        }

        return $messages;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $studentIds = array_column($this->lines(), 'student_id');
            $onSheet = $this->exam()->grades()->whereIn('student_id', $studentIds)->count();

            if ($onSheet !== count($studentIds)) {
                $validator->errors()->add('grades', __('messages.grade_unknown_student'));
            }
        });
    }

    public function exam(): Exam
    {
        /** @var Exam $exam */
        $exam = $this->route('exam');

        return $exam;
    }

    /**
     * The validated lines, typed for the action, with grades written with two decimals.
     *
     * @return list<array{student_id: int, continuous_assessment_grade: string|null, exam_grade: string|null, is_absent: bool, remarks: string|null}>
     */
    public function lines(): array
    {
        $lines = [];

        foreach (array_keys((array) $this->input('grades', [])) as $index) {
            $remarks = $this->string("grades.{$index}.remarks")->trim()->value();

            $lines[] = [
                'student_id' => $this->integer("grades.{$index}.student_id"),
                'continuous_assessment_grade' => $this->grade("grades.{$index}.continuous_assessment_grade"),
                'exam_grade' => $this->grade("grades.{$index}.exam_grade"),
                'is_absent' => $this->boolean("grades.{$index}.is_absent"),
                'remarks' => $remarks === '' ? null : $remarks,
            ];
        }

        return $lines;
    }

    private function grade(string $key): ?string
    {
        return $this->filled($key) ? number_format((float) $this->input($key), 2, '.', '') : null;
    }
}
