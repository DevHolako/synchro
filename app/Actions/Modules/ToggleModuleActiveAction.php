<?php

namespace App\Actions\Modules;

use App\Models\Module;

class ToggleModuleActiveAction
{
    public function execute(Module $module): Module
    {
        $module->update([
            'is_active' => ! $module->is_active,
        ]);

        return $module;
    }
}
