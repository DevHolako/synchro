<?php

namespace App\Http\Controllers\Web\Modules;

use App\Actions\Modules\CreateModuleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\StoreModuleRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ModuleStoreController extends Controller
{
    public function __invoke(StoreModuleRequest $request, CreateModuleAction $action): RedirectResponse
    {
        $module = $action->execute($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Module {$module->name} created successfully.",
        ]);

        return back();
    }
}
