<?php

namespace App\Http\Requests\Exams;

use App\Http\Requests\Concerns\ValidatesConflictOverride;
use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An exam's rooms in order; `force_single_room` (one room, with a justification) seats everyone
 * in it whatever its exam capacity, which needs the override permission.
 */
class AllocateExamRoomsRequest extends FormRequest
{
    use ValidatesConflictOverride;

    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam
            && ($this->user()?->can('update', $exam) ?? false)
            && $this->mayOverride();
    }

    protected function overrideFlag(): string
    {
        return 'force_single_room';
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
        return [
            'room_ids' => ['required', 'array', 'min:1', $this->boolean('force_single_room') ? 'max:1' : 'max:20'],
            'room_ids.*' => ['integer', 'distinct', Rule::exists('rooms', 'id')->where('is_active', true)],
            ...$this->overrideRules(),
        ];
    }

    /**
     * @return list<int>
     */
    public function roomIds(): array
    {
        $this->validated();

        return array_values(array_map('intval', (array) $this->input('room_ids', [])));
    }
}
