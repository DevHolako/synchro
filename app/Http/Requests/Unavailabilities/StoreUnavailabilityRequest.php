<?php

namespace App\Http\Requests\Unavailabilities;

use App\Enums\UnavailabilityType;
use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\TeacherUnavailability;
use App\Support\SchedulingGrid;
use App\Support\SchoolClock;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnavailabilityRequest extends FormRequest
{
    use ReadsTypedInput;

    public function authorize(): bool
    {
        return $this->user()?->can('declare', TeacherUnavailability::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $recurring = UnavailabilityType::RecurringWeekly->value;
        $adHoc = UnavailabilityType::AdHocDate->value;

        return [
            'type' => ['required', Rule::enum(UnavailabilityType::class)],
            'day_of_week' => ["required_if:type,{$recurring}", "prohibited_if:type,{$adHoc}", 'nullable', 'integer', 'between:1,7'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.SchoolClock::today()->toDateString()],
            'end_date' => ["required_if:type,{$adHoc}", 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'start_time' => ["required_if:type,{$recurring}", 'required_with:end_time', 'nullable', $this->gridTime()],
            'end_time' => ["required_if:type,{$recurring}", 'required_with:start_time', 'nullable', $this->gridTime(), 'after:start_time'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => __('messages.unavailability_start_in_past'),
            'end_time.after' => __('messages.unavailability_end_before_start'),
        ];
    }

    /**
     * A quarter-hour time inside the scheduling grid (ADR 0004).
     *
     * @return Closure(string, mixed, Closure(string): mixed): void
     */
    protected function gridTime(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! SchedulingGrid::isGridTime($value)) {
                $fail(__('messages.unavailability_time_slot', SchedulingGrid::bounds()));
            }
        };
    }

    /**
     * The validated declaration, typed for the actions.
     *
     * @return array{type: string, day_of_week: int|null, start_date: string, end_date: string|null, start_time: string|null, end_time: string|null, reason: string}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'type' => $this->string('type')->value(),
            'day_of_week' => $this->nullableInteger('day_of_week'),
            'start_date' => $this->string('start_date')->value(),
            'end_date' => $this->nullableString('end_date'),
            'start_time' => $this->nullableString('start_time'),
            'end_time' => $this->nullableString('end_time'),
            'reason' => $this->string('reason')->value(),
        ];
    }
}
