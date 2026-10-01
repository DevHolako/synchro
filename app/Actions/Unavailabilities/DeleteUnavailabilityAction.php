<?php

namespace App\Actions\Unavailabilities;

use App\Models\TeacherUnavailability;

class DeleteUnavailabilityAction
{
    /**
     * Withdraw an unavailability in any status: removing a constraint needs no approval.
     */
    public function execute(TeacherUnavailability $unavailability): void
    {
        $unavailability->delete();
    }
}
