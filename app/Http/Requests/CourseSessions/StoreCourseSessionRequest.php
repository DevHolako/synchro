<?php

namespace App\Http\Requests\CourseSessions;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\CourseSession;
use App\Models\Module;
use App\Models\StudentGroup;
use App\Services\Scheduling\SoftConflictOverride;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCourseSessionRequest extends FormRequest
{
    use ReadsTypedInput;

    private const string DATETIME_FORMAT = 'Y-m-d H:i';

    /** Open scheduling grid (ADR 0004). */
    private const string GRID_START = '08:00';

    private const string GRID_END = '22:00';

    public function authorize(): bool
    {
        return ($this->user()?->can('create', CourseSession::class) ?? false) && $this->mayOverride();
    }

    /**
     * Asking to override soft conflicts needs its own permission.
     */
    protected function mayOverride(): bool
    {
        return ! $this->boolean('force_override')
            || ($this->user()?->hasPermission(Permission::OverrideSoftConflicts) ?? false);
    }

    /**
     * The teacher defaults to the module's assigned teacher; a justification only counts
     * alongside `force_override`.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->boolean('force_override')) {
            $this->merge(['justification' => null]);
        }

        if (! $this->filled('teacher_id') && $this->filled('module_id')) {
            $this->merge(['teacher_id' => Module::query()->whereKey($this->integer('module_id'))->value('teacher_id')]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->where('is_active', true)],
            'teacher_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Teacher->value)],
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('is_active', true)],
            'student_group_ids' => ['required', 'array', 'min:1'],
            'student_group_ids.*' => ['integer', 'distinct', Rule::exists('student_groups', 'id')->where('is_active', true)],
            'starts_at' => ['required', 'date_format:'.self::DATETIME_FORMAT],
            'ends_at' => ['required', 'date_format:'.self::DATETIME_FORMAT, 'after:starts_at'],
            'force_override' => ['sometimes', 'boolean'],
            'justification' => ['nullable', 'required_if_accepted:force_override', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $errors = $validator->errors();

            if (! $errors->hasAny(['starts_at', 'ends_at'])) {
                $this->validateTimes($validator);
            }

            if (! $errors->hasAny(['module_id', 'student_group_ids', 'student_group_ids.*'])) {
                $this->validateGroupsBelongToModuleProgram($validator);
            }
        });
    }

    /**
     * Same day, inside the 08:00–22:00 grid, on quarter hours.
     */
    private function validateTimes(Validator $validator): void
    {
        $start = CarbonImmutable::createFromFormat(self::DATETIME_FORMAT, $this->string('starts_at')->value());
        $end = CarbonImmutable::createFromFormat(self::DATETIME_FORMAT, $this->string('ends_at')->value());

        if ($start === null || $end === null) {
            return;
        }

        if (! $start->isSameDay($end)) {
            $validator->errors()->add('ends_at', __('messages.course_session_same_day'));

            return;
        }

        if ($start->format('H:i') < self::GRID_START || $end->format('H:i') > self::GRID_END) {
            $validator->errors()->add('starts_at', __('messages.course_session_outside_grid'));
        }

        if ($start->minute % 15 !== 0 || $end->minute % 15 !== 0) {
            $validator->errors()->add('starts_at', __('messages.course_session_quarter_hour'));
        }
    }

    private function validateGroupsBelongToModuleProgram(Validator $validator): void
    {
        $programId = Module::query()->whereKey($this->integer('module_id'))->value('program_id');

        $foreign = StudentGroup::query()
            ->whereKey($this->groupIds())
            ->where('program_id', '!=', $programId)
            ->exists();

        if ($foreign) {
            $validator->errors()->add('student_group_ids', __('messages.course_session_group_program_mismatch'));
        }
    }

    /**
     * @return list<int>
     */
    private function groupIds(): array
    {
        return array_values(array_map('intval', (array) $this->input('student_group_ids', [])));
    }

    /**
     * The user's override of soft conflicts, when they asked for one.
     */
    public function softConflictOverride(): ?SoftConflictOverride
    {
        $user = $this->user();

        if (! $this->boolean('force_override') || $user === null) {
            return null;
        }

        return new SoftConflictOverride($user, $this->string('justification')->trim()->value());
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
