<?php

namespace App\Http\Controllers\Web\AdminSettings;

use App\Actions\Settings\UpdateAdminSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminSettings\UpdateAdminSettingsRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AdminSettingsUpdateController extends Controller
{
    public function __invoke(UpdateAdminSettingsRequest $request, UpdateAdminSettingsAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->execute($user, $request->validated());

        return to_route('admin.settings.index')->with('toast', [
            'type' => 'success',
            'message' => __('messages.admin_settings_updated'),
        ]);
    }
}
