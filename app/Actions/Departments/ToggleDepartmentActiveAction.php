<?php

namespace App\Actions\Departments;

use App\Models\Department;

class ToggleDepartmentActiveAction
{
    public function execute(Department $department): Department
    {
        $department->update([
            'is_active' => ! $department->is_active,
        ]);

        return $department;
    }
}
