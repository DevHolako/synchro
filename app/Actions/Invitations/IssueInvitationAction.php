<?php

namespace App\Actions\Invitations;

use App\Models\InvitationToken;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class IssueInvitationAction
{
    /**
     * Revoke any pending invitations for the user, mint a fresh single-use token
     * valid for 72 hours, and email the signed activation link.
     */
    public function execute(User $user, ?User $invitedBy = null): InvitationToken
    {
        $plainToken = Str::random(64);

        $invitation = DB::transaction(function () use ($user, $invitedBy, $plainToken): InvitationToken {
            $user->invitationTokens()->pending()->update(['revoked_at' => now()]);

            return $user->invitationTokens()->create([
                'invited_by' => $invitedBy?->id,
                'token_hash' => InvitationToken::hashToken($plainToken),
                'expires_at' => now()->addHours(InvitationToken::LIFETIME_HOURS),
            ]);
        });

        $activationUrl = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['token' => $plainToken],
        );

        $user->notify(new UserInvitationNotification($activationUrl, $invitation->expires_at));

        return $invitation;
    }
}
