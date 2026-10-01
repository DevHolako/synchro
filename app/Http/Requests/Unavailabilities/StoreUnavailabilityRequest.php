<?php

namespace App\Http\Requests\Unavailabilities;

use App\Enums\UnavailabilityType;
use App\Models\TeacherUnavailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnavailabilityRequest extends FormRequest
{
    /**
     * A quarter-hour slot inside the 08:00–22:00 scheduling grid (ADR 0004).
     */
    protected const string GRID_TIME = '/^((0[89]|1\d|2[01]):(00|15|30|45)|22:00)$/';

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
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ["required_if:type,{$adHoc}", 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'start_time' => ["required_if:type,{$recurring}", 'required_with:end_time', 'nullable', 'regex:'.self::GRID_TIME],
            'end_time' => ["required_if:type,{$recurring}", 'required_with:start_time', 'nullable', 'regex:'.self::GRID_TIME, 'after:start_time'],
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
            'start_time.regex' => __('messages.unavailability_time_slot'),
            'end_time.regex' => __('messages.unavailability_time_slot'),
            'end_time.after' => __('messages.unavailability_end_before_start'),
        ];
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
            'day_of_week' => $this->filled('day_of_week') ? $this->integer('day_of_week') : null,
            'start_date' => $this->string('start_date')->value(),
            'end_date' => $this->filled('end_date') ? $this->string('end_date')->value() : null,
            'start_time' => $this->filled('start_time') ? $this->string('start_time')->value() : null,
            'end_time' => $this->filled('end_time') ? $this->string('end_time')->value() : null,
            'reason' => $this->string('reason')->value(),
        ];
    }
}
