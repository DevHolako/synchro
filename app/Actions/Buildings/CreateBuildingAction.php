<?php

namespace App\Actions\Buildings;

use App\Models\Building;

class CreateBuildingAction
{
    /**
     * @param array{
     *     campus_id: int,
     *     name: string,
     *     code?: string|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(array $data): Building
    {
        return Building::create([
            'campus_id' => $data['campus_id'],
            'name' => $data['name'],
            'code' => isset($data['code']) ? strtoupper($data['code']) : null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }
}
