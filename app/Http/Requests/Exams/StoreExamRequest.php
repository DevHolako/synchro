<?php

namespace App\Http\Requests\Exams;

use App\Http\Requests\Concerns\ValidatesBookingTimes;
use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExamRequest extends FormRequest
{
    use ValidatesBookingTimes;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Exam::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'exam_period_id' => ['required', 'integer', Rule::exists('exam_periods', 'id')],
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->where('is_active', true)],
            'student_group_ids' => ['required', 'array', 'min:1'],
            'student_group_ids.*' => ['integer', 'distinct', Rule::exists('student_groups', 'id')->where('is_active', true)],
            'starts_at' => ['required', 'date_format:'.self::DATETIME_FORMAT],
            'ends_at' => ['required', 'date_format:'.self::DATETIME_FORMAT, 'after:starts_at'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->hasAny(['starts_at', 'ends_at'])) {
                $this->validateSlotTimes($validator, 'starts_at', 'ends_at', 'exam');
            }

            $this->validateGroupsBelongToModuleProgram($validator, 'exam');
        });
    }

    /**
     * The validated exam, typed for the actions.
     *
     * @return array{exam_period_id: int, module_id: int, student_group_ids: list<int>, starts_at: string, ends_at: string}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'exam_period_id' => $this->integer('exam_period_id'),
            'module_id' => $this->integer('module_id'),
            'student_group_ids' => $this->groupIds(),
            'starts_at' => $this->string('starts_at')->value(),
            'ends_at' => $this->string('ends_at')->value(),
        ];
    }
}
