<?php

namespace App\Actions\Campuses;

use App\Models\Campus;

class CreateCampusAction
{
    /**
     * @param array{
     *     name: string,
     *     code: string,
     *     address?: string|null,
     *     city?: string|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(array $data): Campus
    {
        return Campus::create([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }
}
