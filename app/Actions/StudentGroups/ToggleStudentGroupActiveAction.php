<?php

namespace App\Actions\StudentGroups;

use App\Models\StudentGroup;

class ToggleStudentGroupActiveAction
{
    public function execute(StudentGroup $studentGroup): StudentGroup
    {
        $studentGroup->update([
            'is_active' => ! $studentGroup->is_active,
        ]);

        return $studentGroup;
    }
}
