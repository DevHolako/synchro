<?php

namespace App\Actions\Departments;

use App\Models\Department;
use InvalidArgumentException;

class CreateDepartmentAction
{
    /**
     * @param array{
     *     name: string,
     *     code: string,
     *     description?: string|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(array $data): Department
    {
        $name = trim($data['name'] ?? '');
        $code = trim($data['code'] ?? '');

        if ($name === '') {
            throw new InvalidArgumentException('Department name cannot be empty.');
        }

        if ($code === '') {
            throw new InvalidArgumentException('Department code cannot be empty.');
        }

        return Department::create([
            'name' => $name,
            'code' => strtoupper($code),
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }
}
