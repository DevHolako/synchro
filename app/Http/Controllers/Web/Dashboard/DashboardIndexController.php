<?php

namespace App\Http\Controllers\Web\Dashboard;

use App\Actions\Dashboard\GetDashboardDataAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardIndexController extends Controller
{
    public function __invoke(Request $request, GetDashboardDataAction $action): Response
    {
        /** @var User $user */
        $user = $request->user();

        $data = $action->execute($user);

        return Inertia::render('dashboard', $data);
    }
}
