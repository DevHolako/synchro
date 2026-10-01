<?php

namespace App\Http\Requests\Unavailabilities;

use App\Models\TeacherUnavailability;

class UpdateUnavailabilityRequest extends StoreUnavailabilityRequest
{
    public function authorize(): bool
    {
        /** @var TeacherUnavailability $unavailability */
        $unavailability = $this->route('unavailability');

        return $this->user()?->can('update', $unavailability) ?? false;
    }
}
