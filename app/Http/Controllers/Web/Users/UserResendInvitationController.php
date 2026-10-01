<?php

namespace App\Http\Controllers\Web\Users;

use App\Actions\Invitations\ResendInvitationAction;
use App\Exceptions\InvitationException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserResendInvitationController extends Controller
{
    public function __invoke(Request $request, User $user, ResendInvitationAction $action): RedirectResponse
    {
        Gate::authorize('resendInvitation', $user);

        try {
            $action->execute($user, $request->user());
        } catch (InvitationException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.invitation_resent', ['email' => $user->email]),
        ]);

        return back();
    }
}
