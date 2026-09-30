<?php

namespace App\Actions\Departments;

use App\Models\Department;
use InvalidArgumentException;

class UpdateDepartmentAction
{
    /**
     * @param array{
     *     name?: string,
     *     code?: string,
     *     description?: string|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(Department $department, array $data): Department
    {
        $payload = [];

        if (array_key_exists('name', $data)) {
            $name = trim($data['name']);
            if ($name === '') {
                throw new InvalidArgumentException('Department name cannot be empty.');
            }
            $payload['name'] = $name;
        }

        if (array_key_exists('code', $data)) {
            $code = trim($data['code']);
            if ($code === '') {
                throw new InvalidArgumentException('Department code cannot be empty.');
            }
            $payload['code'] = strtoupper($code);
        }

        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'];
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        $department->update($payload);

        return $department;
    }
}
