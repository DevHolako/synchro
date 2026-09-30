<?php

namespace App\Http\Controllers\Web\Modules;

use App\Actions\Modules\UpdateModuleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\UpdateModuleRequest;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ModuleUpdateController extends Controller
{
    public function __invoke(UpdateModuleRequest $request, Module $module, UpdateModuleAction $action): RedirectResponse
    {
        $module = $action->execute($module, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.module_updated', ['name' => $module->name]),
        ]);

        return back();
    }
}
