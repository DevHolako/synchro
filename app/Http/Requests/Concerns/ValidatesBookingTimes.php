<?php

namespace App\Http\Requests\Concerns;

use App\Models\Module;
use App\Models\StudentGroup;
use App\Support\SchedulingGrid;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

/**
 * What every booking write shares, course session or exam: a same-day window on the
 * scheduling grid, and groups from the module's program.
 *
 * `$keyPrefix` starts the translation keys, so each booking speaks of itself.
 */
trait ValidatesBookingTimes
{
    protected const string DATETIME_FORMAT = 'Y-m-d H:i';

    /**
     * Same day, inside the scheduling grid, on quarter hours.
     */
    protected function validateSlotTimes(Validator $validator, string $startKey, string $endKey, string $keyPrefix = 'course_session'): void
    {
        $start = CarbonImmutable::createFromFormat(self::DATETIME_FORMAT, $this->string($startKey)->value());
        $end = CarbonImmutable::createFromFormat(self::DATETIME_FORMAT, $this->string($endKey)->value());

        if ($start === null || $end === null) {
            return;
        }

        if (! $start->isSameDay($end)) {
            $validator->errors()->add($endKey, __("messages.{$keyPrefix}_same_day"));

            return;
        }

        if (! SchedulingGrid::contains($start, $end)) {
            $validator->errors()->add($startKey, __("messages.{$keyPrefix}_outside_grid", SchedulingGrid::bounds()));
        }

        if (! SchedulingGrid::isOnStep($start) || ! SchedulingGrid::isOnStep($end)) {
            $validator->errors()->add($startKey, __("messages.{$keyPrefix}_quarter_hour"));
        }
    }

    /**
     * Runs once the module and groups are individually valid.
     */
    protected function validateGroupsBelongToModuleProgram(Validator $validator, string $keyPrefix = 'course_session'): void
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
            $validator->errors()->add('student_group_ids', __("messages.{$keyPrefix}_group_program_mismatch"));
        }
    }

    /**
     * @return list<int>
     */
    protected function groupIds(): array
    {
        return array_values(array_map('intval', (array) $this->input('student_group_ids', [])));
    }
}
