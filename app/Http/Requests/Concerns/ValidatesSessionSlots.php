<?php

namespace App\Http\Requests\Concerns;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Module;
use App\Models\StudentGroup;
use App\Services\Scheduling\SoftConflictOverride;
use App\Support\SchedulingGrid;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * What every course session write shares, whether one session or a batch: the module,
 * teacher, room and groups, the soft-conflict override, and the rules for a slot's times.
 */
trait ValidatesSessionSlots
{
    protected const string DATETIME_FORMAT = 'Y-m-d H:i';

    /**
     * Asking to override soft conflicts needs its own permission.
     */
    protected function mayOverride(): bool
    {
        return ! $this->boolean('force_override')
            || ($this->user()?->hasPermission(Permission::OverrideSoftConflicts) ?? false);
    }

    /**
     * A justification only counts alongside `force_override`.
     */
    protected function prepareOverrideInput(): void
    {
        if (! $this->boolean('force_override')) {
            $this->merge(['justification' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function overrideRules(): array
    {
        return [
            'force_override' => ['sometimes', 'boolean'],
            'justification' => [
                'nullable',
                'required_if_accepted:force_override',
                'string',
                'min:'.SoftConflictOverride::MIN_JUSTIFICATION,
                'max:'.SoftConflictOverride::MAX_JUSTIFICATION,
            ],
        ];
    }

    /**
     * The teacher defaults to the module's assigned teacher; a justification only counts
     * alongside `force_override`.
     */
    protected function prepareSessionInput(): void
    {
        $this->prepareOverrideInput();

        if (! $this->filled('teacher_id') && $this->filled('module_id')) {
            $this->merge(['teacher_id' => Module::query()->whereKey($this->integer('module_id'))->value('teacher_id')]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function sessionRules(): array
    {
        return [
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->where('is_active', true)],
            'teacher_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Teacher->value)],
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('is_active', true)],
            'student_group_ids' => ['required', 'array', 'min:1'],
            'student_group_ids.*' => ['integer', 'distinct', Rule::exists('student_groups', 'id')->where('is_active', true)],
            ...$this->overrideRules(),
        ];
    }

    /**
     * Same day, inside the scheduling grid, on quarter hours.
     */
    protected function validateSlotTimes(Validator $validator, string $startKey, string $endKey): void
    {
        $start = CarbonImmutable::createFromFormat(self::DATETIME_FORMAT, $this->string($startKey)->value());
        $end = CarbonImmutable::createFromFormat(self::DATETIME_FORMAT, $this->string($endKey)->value());

        if ($start === null || $end === null) {
            return;
        }

        if (! $start->isSameDay($end)) {
            $validator->errors()->add($endKey, __('messages.course_session_same_day'));

            return;
        }

        if (! SchedulingGrid::contains($start, $end)) {
            $validator->errors()->add($startKey, __('messages.course_session_outside_grid', SchedulingGrid::bounds()));
        }

        if (! SchedulingGrid::isOnStep($start) || ! SchedulingGrid::isOnStep($end)) {
            $validator->errors()->add($startKey, __('messages.course_session_quarter_hour'));
        }
    }

    /**
     * Runs once the module and groups are individually valid.
     */
    protected function validateGroupsBelongToModuleProgram(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['module_id', 'student_group_ids', 'student_group_ids.*'])) {
            return;
        }

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
    protected function groupIds(): array
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
}
