<?php

namespace App\Actions\Campuses;

use App\Models\Campus;

class UpdateCampusAction
{
    /**
     * @param array{
     *     name?: string,
     *     code?: string,
     *     address?: string|null,
     *     city?: string|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(Campus $campus, array $data): Campus
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $campus->update($data);

        return $campus->refresh();
    }
}
