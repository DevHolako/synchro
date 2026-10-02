<?php

namespace App\Http\Requests\Exams;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\ValidatesConflictOverride;
use App\Models\Exam;
use App\Models\ExamRoomAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One exam room's lead invigilator and assistants, with an override for declared unavailabilities.
 */
class AssignInvigilatorsRequest extends FormRequest
{
    use ValidatesConflictOverride;

    public function authorize(): bool
    {
        $exam = $this->route('exam');
        $room = $this->route('assignment');

        return $exam instanceof Exam
            && $room instanceof ExamRoomAssignment
            && $room->exam_id === $exam->id
            && ($this->user()?->can('update', $exam) ?? false)
            && $this->mayOverride();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareOverrideInput();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teacher = Rule::exists('users', 'id')->where('role', UserRole::Teacher->value);

        return [
            'lead_id' => ['required', 'integer', $teacher],
            'assistant_ids' => ['present', 'array', 'max:10'],
            'assistant_ids.*' => ['integer', 'distinct', 'different:lead_id', $teacher],
            ...$this->overrideRules(),
        ];
    }

    public function leadId(): int
    {
        $this->validated();

        return $this->integer('lead_id');
    }

    /**
     * @return list<int>
     */
    public function assistantIds(): array
    {
        $this->validated();

        return array_values(array_map('intval', (array) $this->input('assistant_ids', [])));
    }
}
