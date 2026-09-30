<?php

namespace App\Http\Controllers\Web\Modules;

use App\Actions\Modules\ToggleModuleActiveAction;
use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModuleToggleActiveController extends Controller
{
    public function __invoke(Request $request, Module $module, ToggleModuleActiveAction $action): RedirectResponse
    {
        if (! $request->user()?->can('update', $module)) {
            abort(403, 'Unauthorized to update module status.');
        }

        $module = $action->execute($module);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Module {$module->name} ".($module->is_active ? 'activated' : 'deactivated').' successfully.',
        ]);

        return back();
    }
}
