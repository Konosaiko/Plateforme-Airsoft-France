<?php

namespace App\Service\Association;

use App\Entity\Association\Association;
use App\Entity\Association\AssociationMember;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AssociationMemberService
{
    private $entityManager;
    private $notificationService;

    public function __construct(EntityManagerInterface $entityManager, NotificationService $notificationService)
    {
        $this->entityManager = $entityManager;
        $this->notificationService = $notificationService;
    }

    public function inviteMember(Association $association, User $invitedUser, string $role, User $invitingUser): void
    {
        if (!$this->canManageMembers($association, $invitingUser)) {
            throw new AccessDeniedException('You do not have permission to do that.');
        }

        $existingMember = $this->entityManager->getRepository(AssociationMember::class)->findOneBy([
            'association' => $association,
            'user' => $invitedUser,
        ]);

        if ($existingMember) {
            throw new AccessDeniedException('User is already member.');
        }

        $member = new AssociationMember();
        $member->setAssociation($association);
        $member->setUser($invitedUser);
        $member->setRole($role);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        $this->notificationService->createNotification(
            $invitedUser,
            "You have been invited to join {$association->getName()} as {$role}!"
        );
    }

    public function changeMemberRole(AssociationMember $member, string $newRole, User $changingUser): void
    {
        if (!$this->canManageMembers($member->getAssociation(), $changingUser)) {
            throw new AccessDeniedException('You do not have permission to do that.');
        }

        if ($member->isOwner()) {
            throw new \RuntimeException('Canot change the role of the owner.');
        }

        $member->setRole($newRole);
        $this->entityManager->flush();

        $this->notificationService->createNotification(
            $member->getUser(),
            "Your role in {$member->getAssociation()->getName()} has been changed to {$newRole}"
        );
    }

    public function removeMember(AssociationMember $member, User $removingUser): void
    {
        if (!$this->canManageMembers($member->getAssociation(), $removingUser)) {
            throw new AccessDeniedException('You do not have permission to do that.');
        }

        if ($member->isOwner()) {
            throw new \RuntimeException('Cannot remove the owner.');
        }

        $this->entityManager->remove($member);
        $this->entityManager->flush();

        $this->notificationService->createNotification(
            $removingUser,
            "You have been removed from {$member->getAssociation()->getName()}"
        );


    }

    public function canManageMembers(Association $association, User $user): bool
    {
        $member = $this->entityManager->getRepository(AssociationMember::class)->findOneBy([
            'association' => $association,
            'user' => $user,
        ]);

        return $member && ($member->isOwner() || $member->isAdmin());
    }

}