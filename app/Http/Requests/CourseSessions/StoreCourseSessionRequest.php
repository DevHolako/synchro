<?php

namespace App\Http\Requests\CourseSessions;

use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Http\Requests\Concerns\ValidatesSessionSlots;
use App\Models\CourseSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCourseSessionRequest extends FormRequest
{
    use ReadsTypedInput;
    use ValidatesSessionSlots;

    public function authorize(): bool
    {
        return ($this->user()?->can('create', CourseSession::class) ?? false) && $this->mayOverride();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSessionInput();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->sessionRules(),
            'starts_at' => ['required', 'date_format:'.self::DATETIME_FORMAT],
            'ends_at' => ['required', 'date_format:'.self::DATETIME_FORMAT, 'after:starts_at'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->hasAny(['starts_at', 'ends_at'])) {
                $this->validateSlotTimes($validator, 'starts_at', 'ends_at');
            }

            $this->validateGroupsBelongToModuleProgram($validator);
        });
    }

    /**
     * The validated session, typed for the actions.
     *
     * @return array{module_id: int, teacher_id: int, room_id: int, student_group_ids: list<int>, starts_at: string, ends_at: string}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'module_id' => $this->integer('module_id'),
            'teacher_id' => $this->integer('teacher_id'),
            'room_id' => $this->integer('room_id'),
            'student_group_ids' => $this->groupIds(),
            'starts_at' => $this->string('starts_at')->value(),
            'ends_at' => $this->string('ends_at')->value(),
        ];
    }
}
