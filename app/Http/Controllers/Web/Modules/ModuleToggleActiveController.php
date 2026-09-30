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

        $status = $module->is_active ? __('messages.activated') : __('messages.deactivated');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.module_status_updated', ['name' => $module->name, 'status' => $status]),
        ]);

        return back();
    }
}
