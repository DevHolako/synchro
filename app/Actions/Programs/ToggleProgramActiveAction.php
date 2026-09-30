<?php

namespace App\Actions\Programs;

use App\Models\Program;

class ToggleProgramActiveAction
{
    public function execute(Program $program): Program
    {
        $program->update([
            'is_active' => ! $program->is_active,
        ]);

        return $program;
    }
}
