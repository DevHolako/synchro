<?php

namespace App\Http\Requests\CourseSessions;

use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Http\Requests\Concerns\ValidatesSessionSlots;
use App\Models\CourseSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Several sessions of one module, teacher, room and set of groups, one per slot (Part 03 / Ticket 02).
 */
class StoreCourseSessionBatchRequest extends FormRequest
{
    use ReadsTypedInput;
    use ValidatesSessionSlots;

    /** Well above a semester of weekly sessions; each slot is locked and checked on save. */
    public const int MAX_SLOTS = 60;

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
            'slots' => ['required', 'array', 'min:1', 'max:'.self::MAX_SLOTS],
            'slots.*' => ['required', 'array:starts_at,ends_at'],
            'slots.*.starts_at' => ['required', 'date_format:'.self::DATETIME_FORMAT, 'distinct'],
            'slots.*.ends_at' => ['required', 'date_format:'.self::DATETIME_FORMAT, 'after:slots.*.starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slots.*.starts_at.distinct' => __('messages.course_session_batch_duplicate_slot'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (array_keys((array) $this->input('slots', [])) as $index) {
                $startKey = "slots.{$index}.starts_at";
                $endKey = "slots.{$index}.ends_at";

                if (! $validator->errors()->hasAny([$startKey, $endKey])) {
                    $this->validateSlotTimes($validator, $startKey, $endKey);
                }
            }

            $this->validateGroupsBelongToModuleProgram($validator);
        });
    }

    /**
     * The validated batch, typed for the actions.
     *
     * @return array{module_id: int, teacher_id: int, room_id: int, student_group_ids: list<int>, slots: list<array{starts_at: string, ends_at: string}>}
     */
    public function payload(): array
    {
        $this->validated();

        $slots = [];

        foreach (array_keys((array) $this->input('slots', [])) as $index) {
            $slots[] = [
                'starts_at' => $this->string("slots.{$index}.starts_at")->value(),
                'ends_at' => $this->string("slots.{$index}.ends_at")->value(),
            ];
        }

        return [
            'module_id' => $this->integer('module_id'),
            'teacher_id' => $this->integer('teacher_id'),
            'room_id' => $this->integer('room_id'),
            'student_group_ids' => $this->groupIds(),
            'slots' => $slots,
        ];
    }
}
