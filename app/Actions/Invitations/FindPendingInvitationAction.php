<?php

namespace App\Actions\Invitations;

use App\Exceptions\InvitationException;
use App\Models\InvitationToken;

class FindPendingInvitationAction
{
    /**
     * Resolve a plain token to a usable invitation (not expired, consumed, or revoked).
     *
     * @throws InvitationException
     */
    public function execute(string $plainToken): InvitationToken
    {
        $invitation = InvitationToken::query()
            ->with('user')
            ->where('token_hash', InvitationToken::hashToken($plainToken))
            ->first();

        if ($invitation === null || ! $invitation->isUsable() || $invitation->user->isActive()) {
            throw InvitationException::invalid();
        }

        return $invitation;
    }
}
