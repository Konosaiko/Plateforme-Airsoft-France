<?php

namespace App\Repository;

use App\Entity\Association\Association;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Association>
 */
class AssociationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Association::class);
    }

    public function findAssociationsForUser(?User $user): array
    {
        if (!$user) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->innerJoin('a.associationMembers', 'am')
            ->where('am.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
