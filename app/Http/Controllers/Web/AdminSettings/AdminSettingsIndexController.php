<?php

namespace App\Http\Controllers\Web\AdminSettings;

use App\Actions\Settings\GetAdminSettingsAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingsIndexController extends Controller
{
    public function __invoke(Request $request, GetAdminSettingsAction $action): Response
    {
        /** @var User $user */
        $user = $request->user();

        $data = $action->execute($user);

        return Inertia::render('admin/settings/index', $data);
    }
}
