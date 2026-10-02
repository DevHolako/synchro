<?php

namespace App\Http\Requests\Exams;

use App\Enums\Permission;
use App\Models\Exam;
use App\Services\Scheduling\SoftConflictOverride;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An exam's rooms in order; `force_single_room` (one room, with a justification) seats everyone
 * in it whatever its exam capacity, which needs the override permission.
 */
class AllocateExamRoomsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');
        $user = $this->user();

        return $exam instanceof Exam
            && $user !== null
            && $user->can('update', $exam)
            && (! $this->boolean('force_single_room') || $user->hasPermission(Permission::OverrideSoftConflicts));
    }

    protected function prepareForValidation(): void
    {
        if (! $this->boolean('force_single_room')) {
            $this->merge(['justification' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'room_ids' => ['required', 'array', 'min:1', $this->boolean('force_single_room') ? 'max:1' : 'max:20'],
            'room_ids.*' => ['integer', 'distinct', Rule::exists('rooms', 'id')->where('is_active', true)],
            'force_single_room' => ['sometimes', 'boolean'],
            'justification' => [
                'nullable',
                'required_if_accepted:force_single_room',
                'string',
                'min:'.SoftConflictOverride::MIN_JUSTIFICATION,
                'max:'.SoftConflictOverride::MAX_JUSTIFICATION,
            ],
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

    /**
     * The justified decision to seat everyone in the one room, when asked for.
     */
    public function forceSingleRoom(): ?SoftConflictOverride
    {
        $user = $this->user();

        if (! $this->boolean('force_single_room') || $user === null) {
            return null;
        }

        return new SoftConflictOverride($user, $this->string('justification')->trim()->value());
    }
}
