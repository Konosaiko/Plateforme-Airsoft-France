<?php

namespace App\Security\Voter;

use App\Entity\Association;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class AssociationVoter extends Voter
{
    const VIEW = 'ASSOCIATION_VIEW';
    const EDIT = 'ASSOCIATION_EDIT';
    const DELETE = 'ASSOCIATION_DELETE';
    const MANAGE_MEMBERS = 'ASSOCIATION_MANAGE_MEMBERS';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::MANAGE_MEMBERS])
            && $subject instanceof Association;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Association $association */
        $association = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($association, $user);
            case self::EDIT:
                return $this->canEdit($association, $user);
            case self::DELETE:
                return $this->canDelete($association, $user);
            case self::MANAGE_MEMBERS:
                return $this->canManageMembers($association, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView(Association $association, User $user): bool
    {
        return true;
    }

    private function canEdit(Association $association, User $user): bool
    {
        return $this->isOwnerOrAdmin($association, $user);
    }

    private function canDelete(Association $association, User $user): bool
    {
        return $this->isOwner($association, $user);
    }

    private function canManageMembers(Association $association, User $user): bool
    {
        return $this->isOwnerOrAdmin($association, $user);
    }

    private function isOwner(Association $association, User $user): bool
    {
        return $user === $association->getOwner();
    }

    private function isOwnerOrAdmin(Association $association, User $user): bool
    {
        if ($this->isOwner($association, $user)) {
            return true;
        }

        foreach ($association->getAssociationMembers() as $member) {
            if ($member->getUser() === $user && $member->isAdmin()) {
                return true;
            }
        }

        return false;
    }
}