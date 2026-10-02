<?php

namespace App\Http\Requests\Exams;

use App\Http\Requests\Concerns\ValidatesBookingTimes;
use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A published exam's new time and, optionally, new rooms; the reason is kept in the audit and
 * sent to everyone concerned, and the coordinator confirms the convocations will be replaced.
 */
class EmergencyRescheduleExamRequest extends FormRequest
{
    use ValidatesBookingTimes;

    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam && ($this->user()?->can('update', $exam) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date_format:'.self::DATETIME_FORMAT],
            'ends_at' => ['required', 'date_format:'.self::DATETIME_FORMAT, 'after:starts_at'],
            'room_ids' => ['nullable', 'array', 'min:1', 'max:20'],
            'room_ids.*' => ['integer', 'distinct', Rule::exists('rooms', 'id')->where('is_active', true)],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'confirmed' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->hasAny(['starts_at', 'ends_at'])) {
                $this->validateSlotTimes($validator, 'starts_at', 'ends_at', 'exam');
            }
        });
    }

    /**
     * @return array{starts_at: string, ends_at: string, room_ids: list<int>|null}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'starts_at' => $this->string('starts_at')->value(),
            'ends_at' => $this->string('ends_at')->value(),
            'room_ids' => $this->filled('room_ids') ? array_values(array_map('intval', (array) $this->input('room_ids'))) : null,
        ];
    }

    public function reason(): string
    {
        return $this->string('reason')->trim()->value();
    }
}
