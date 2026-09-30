<?php

namespace App\Actions\Buildings;

use App\Models\Building;

class ToggleBuildingActiveAction
{
    public function execute(Building $building): Building
    {
        $building->update([
            'is_active' => ! $building->is_active,
        ]);

        return $building->refresh();
    }
}
