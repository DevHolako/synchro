<?php

namespace App\Http\Requests\Concerns;

use App\Enums\UserRole;
use App\Models\Module;
use Illuminate\Validation\Rule;

/**
 * What every course session write shares, whether one session or a batch: the module,
 * teacher, room and groups, the soft-conflict override, and the rules for a slot's times.
 */
trait ValidatesSessionSlots
{
    use ValidatesBookingTimes;
    use ValidatesConflictOverride;

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
}
