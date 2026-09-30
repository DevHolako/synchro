<?php

namespace App\Actions\Campuses;

use App\Models\Campus;

class ToggleCampusActiveAction
{
    public function execute(Campus $campus): Campus
    {
        $campus->update([
            'is_active' => ! $campus->is_active,
        ]);

        return $campus->refresh();
    }
}
