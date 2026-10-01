<?php

namespace App\Http\Requests\Unavailabilities;

use App\Enums\UnavailabilityStatus;
use App\Models\TeacherUnavailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewUnavailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var TeacherUnavailability $unavailability */
        $unavailability = $this->route('unavailability');

        return $this->user()?->can('review', $unavailability) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([UnavailabilityStatus::Approved->value, UnavailabilityStatus::Rejected->value])],
            'review_note' => ['required_if:decision,'.UnavailabilityStatus::Rejected->value, 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'review_note.required_if' => __('messages.unavailability_rejection_note_required'),
        ];
    }

    public function decision(): UnavailabilityStatus
    {
        return UnavailabilityStatus::from($this->validated('decision'));
    }
}
