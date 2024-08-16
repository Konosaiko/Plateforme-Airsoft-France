<?php

namespace App\Security\Voter;

use App\Entity\Team;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TeamVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['TEAM_EDIT', 'TEAM_DELETE', 'TEAM_MANAGE'])
            && $subject instanceof Team;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Team $team */
        $team = $subject;

        return match ($attribute) {
            'TEAM_EDIT', 'TEAM_DELETE', 'TEAM_MANAGE' => $this->canEditOrDelete($team, $user),
            default => false,
        };

    }

    private function canEditOrDelete(Team $team, User $user): bool
    {
        return $user === $team->getCreator();
    }
}
