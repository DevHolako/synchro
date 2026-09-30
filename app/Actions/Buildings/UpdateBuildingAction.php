<?php

namespace App\Actions\Buildings;

use App\Models\Building;

class UpdateBuildingAction
{
    /**
     * @param array{
     *     campus_id?: int,
     *     name?: string,
     *     code?: string|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(Building $building, array $data): Building
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $building->update($data);

        return $building->refresh();
    }
}
