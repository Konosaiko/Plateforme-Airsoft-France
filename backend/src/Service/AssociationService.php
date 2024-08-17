<?php

namespace App\Service;

use App\Entity\Association;
use App\Entity\AssociationMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AssociationService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public function createAssociation(Association $association, User $owner): Association
    {
        $this->entityManager->persist($association);

        $ownerMember = new AssociationMember();
        $ownerMember->setAssociation($association);
        $ownerMember->setUser($owner);
        $ownerMember->setRole('OWNER');

        $this->entityManager->persist($ownerMember);
        $this->entityManager->flush();

        return $association;
    }

    public function updateAssociation(Association $association, User $user): Association
    {
        if (!$this->canManageAssociation($association, $user)) {
            throw new AccessDeniedException('You do not have permission to update this association.');
        }

        $this->entityManager->flush();

        return $association;
    }

    public function deleteAssociation(Association $association, User $user): Association
    {
        if (!$this->canManageAssociation($association, $user)) {
            throw new AccessDeniedException('You do not have permission to delete this association.');
        }

        $this->entityManager->remove($association);
        $this->entityManager->flush();
        return $association;
    }

    public function addMember(Association $association, User $user, string $role = 'MEMBER'): AssociationMember
    {
        $member = new AssociationMember();
        $member->setAssociation($association);
        $member->setUser($user);
        $member->setRole($role);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    public function removeMember(Association $association, User $user, User $currentUser): void
    {
        if (!$this->canManageAssociation($association, $currentUser) && $user !== $currentUser) {
            throw new AccessDeniedException('You do not have permission to remove this member.');
        }

        $member = $this->entityManager->getRepository(AssociationMember::class)->findOneBy([
            'association' => $association,
            'user' => $user
        ]);

        if (!$member) {
            throw new \RuntimeException('User is not a member of this association.');
        }

        $this->entityManager->remove($member);
        $this->entityManager->flush();
    }

    public function canManageAssociation(Association $association, User $user): bool
    {
        $member = $this->entityManager->getRepository(AssociationMember::class)->findOneBy([
            'association' => $association,
            'user' => $user
        ]);

        return $member && ($member->isOwner() || $member->isAdmin());
    }

    public function getAllAssociations(): array
    {
        return $this->entityManager->getRepository(Association::class)->findAll();
    }

    public function getAssociation(int $id): ?Association
    {
        return $this->entityManager->getRepository(Association::class)->find($id);
    }
}