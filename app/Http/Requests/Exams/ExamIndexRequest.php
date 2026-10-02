<?php

namespace App\Http\Requests\Exams;

use App\Enums\ExamState;
use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamIndexRequest extends FormRequest
{
    use ReadsTypedInput;

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Exam::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'integer'],
            'state' => ['nullable', Rule::enum(ExamState::class)],
            'program_id' => ['nullable', 'integer'],
            'group_id' => ['nullable', 'integer'],
        ];
    }

    public function periodId(): ?int
    {
        return $this->nullableInteger('period');
    }

    /**
     * @return array{state: ExamState|null, program_id: int|null, group_id: int|null}
     */
    public function filters(): array
    {
        return [
            'state' => ExamState::tryFrom($this->string('state')->value()),
            'program_id' => $this->nullableInteger('program_id'),
            'group_id' => $this->nullableInteger('group_id'),
        ];
    }
}
