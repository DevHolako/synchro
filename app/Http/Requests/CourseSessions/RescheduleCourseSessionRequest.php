<?php

namespace App\Http\Requests\CourseSessions;

use App\Http\Requests\Concerns\ValidatesSessionSlots;
use App\Models\CourseSession;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * New times for a session that has not started, from a calendar drag or resize (Part 03 / Ticket 03).
 */
class RescheduleCourseSessionRequest extends FormRequest
{
    use ValidatesSessionSlots;

    public function authorize(): bool
    {
        return ($this->user()?->can('update', $this->session()) ?? false) && $this->mayOverride();
    }

    protected function prepareForValidation(): void
    {
        if (! $this->boolean('force_override')) {
            $this->merge(['justification' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date_format:'.self::DATETIME_FORMAT],
            'ends_at' => ['required', 'date_format:'.self::DATETIME_FORMAT, 'after:starts_at'],
            'force_override' => ['sometimes', 'boolean'],
            'justification' => ['nullable', 'required_if_accepted:force_override', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->session()->hasStarted()) {
                $validator->errors()->add('starts_at', __('messages.course_session_started'));

                return;
            }

            if ($validator->errors()->hasAny(['starts_at', 'ends_at'])) {
                return;
            }

            $this->validateSlotTimes($validator, 'starts_at', 'ends_at');

            if (CarbonImmutable::createFromFormat(self::DATETIME_FORMAT, $this->string('starts_at')->value())?->isPast()) {
                $validator->errors()->add('starts_at', __('messages.course_session_in_past'));
            }
        });
    }

    public function session(): CourseSession
    {
        /** @var CourseSession $session */
        $session = $this->route('session');

        return $session;
    }

    /**
     * @return array{starts_at: string, ends_at: string}
     */
    public function times(): array
    {
        $this->validated();

        return [
            'starts_at' => $this->string('starts_at')->value(),
            'ends_at' => $this->string('ends_at')->value(),
        ];
    }
}
