<?php

namespace App\Security\Voter;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Association\AssociationMember;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

class EventVoter extends Voter
{
    const CREATE = 'create';
    const EDIT = 'edit';
    const DELETE = 'delete';

    private $security;
    private $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::CREATE, self::EDIT, self::DELETE])
            && ($subject instanceof Event || $subject === null);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Si l'utilisateur est un admin global, il a tous les droits
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var Event $event */
        $event = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($event, $user);
            case self::EDIT:
            case self::DELETE:
                return $this->canEditOrDelete($event, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(?Event $event, User $user): bool
    {
        // Vérifiez si l'utilisateur est un admin ou le créateur de l'association
        if ($event && $event->getAssociation()) {
            return $this->isAdminOrCreator($event->getAssociation(), $user);
        }
        return false;
    }

    private function canEditOrDelete(Event $event, User $user): bool
    {
        // Vérifiez si l'utilisateur est un admin ou le créateur de l'association
        return $this->isAdminOrCreator($event->getAssociation(), $user);
    }

    private function isAdminOrCreator($association, User $user): bool
    {
        $memberRepository = $this->entityManager->getRepository(AssociationMember::class);
        $member = $memberRepository->findOneBy([
            'association' => $association,
            'user' => $user
        ]);

        return $member && ($member->getRole() === 'ADMIN' || $member->getRole() === 'OWNER');
    }
}