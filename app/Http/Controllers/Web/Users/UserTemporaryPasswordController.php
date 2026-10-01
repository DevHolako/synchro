<?php

namespace App\Http\Controllers\Web\Users;

use App\Actions\Users\IssueTemporaryPasswordAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserTemporaryPasswordController extends Controller
{
    public function __invoke(User $user, IssueTemporaryPasswordAction $action): RedirectResponse
    {
        Gate::authorize('issueTemporaryPassword', $user);

        $temporaryPassword = $action->execute($user);

        Inertia::flash([
            'toast' => [
                'type' => 'success',
                'message' => __('messages.temporary_password_issued', ['name' => $user->name]),
            ],
            'temporary_password' => [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $temporaryPassword,
            ],
        ]);

        return back();
    }
}
