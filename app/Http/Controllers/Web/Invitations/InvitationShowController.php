<?php

namespace App\Http\Controllers\Web\Invitations;

use App\Actions\Invitations\FindPendingInvitationAction;
use App\Exceptions\InvitationException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class InvitationShowController extends Controller
{
    public function __invoke(Request $request, string $token, FindPendingInvitationAction $action): Response
    {
        try {
            if (! $request->hasValidSignature()) {
                throw InvitationException::invalid();
            }

            $invitation = $action->execute($token);
        } catch (InvitationException) {
            return Inertia::render('auth/invitation-invalid')
                ->toResponse($request)
                ->setStatusCode(Response::HTTP_FORBIDDEN);
        }

        return Inertia::render('auth/accept-invitation', [
            'name' => $invitation->user->name,
            'email' => $invitation->user->email,
            'expiresAt' => $invitation->expires_at->toIso8601String(),
            'activateUrl' => URL::temporarySignedRoute(
                'invitations.activate',
                $invitation->expires_at,
                ['token' => $token],
            ),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ])->toResponse($request);
    }
}
