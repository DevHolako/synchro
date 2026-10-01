<?php

namespace App\Actions\Invitations;

use App\Exceptions\InvitationException;
use App\Models\InvitationToken;
use App\Models\User;

class ResendInvitationAction
{
    public function __construct(private readonly IssueInvitationAction $issueInvitation) {}

    /**
     * Regenerate the Invitation Token of a not-yet-activated user, invalidating previous links.
     *
     * @throws InvitationException
     */
    public function execute(User $user, ?User $invitedBy = null): InvitationToken
    {
        if ($user->isActive()) {
            throw InvitationException::accountAlreadyActive();
        }

        return $this->issueInvitation->execute($user, $invitedBy);
    }
}
