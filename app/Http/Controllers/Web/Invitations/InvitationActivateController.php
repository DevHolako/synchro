<?php

namespace App\Http\Controllers\Web\Invitations;

use App\Actions\Invitations\ActivateUserInvitationAction;
use App\Actions\Invitations\FindPendingInvitationAction;
use App\Exceptions\InvitationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invitations\ActivateInvitationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvitationActivateController extends Controller
{
    public function __invoke(
        ActivateInvitationRequest $request,
        string $token,
        FindPendingInvitationAction $findInvitation,
        ActivateUserInvitationAction $activate,
    ): RedirectResponse {
        try {
            $user = $activate->execute($findInvitation->execute($token), $request->validated('password'));
        } catch (InvitationException $exception) {
            abort(403, $exception->getMessage());
        }

        Auth::login($user);
        $request->session()->regenerate();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.invitation_activated', ['name' => $user->name]),
        ]);

        return redirect()->route('dashboard');
    }
}
