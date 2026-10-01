<?php

namespace App\Http\Controllers\Web\Users;

use App\Actions\Users\ProvisionUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UserStoreController extends Controller
{
    public function __invoke(StoreUserRequest $request, ProvisionUserAction $action): RedirectResponse
    {
        $user = $action->execute($request->payload(), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.user_provisioned', ['name' => $user->name, 'email' => $user->email]),
        ]);

        return back();
    }
}
