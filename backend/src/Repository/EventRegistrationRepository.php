<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventRegistration>
 */
class EventRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventRegistration::class);
    }

    public function findPendingRegistrationsByEvent(Event $event): array
    {
        return $this->createQueryBuilder('er')
            ->andWhere('er.event = :event')
            ->andWhere('er.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getResult();
    }

    public function countActiveRegistrations(Event $event): int
    {
        return $this->createQueryBuilder('er')
            ->select('COUNT(er.id)')
            ->where('er.event = :event')
            ->andWhere('er.status IN (:statuses)')
            ->setParameter('event', $event)
            ->setParameter('statuses', ['confirmed', 'pending'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneByUserAndEvent(User $user, Event $event): ?EventRegistration
    {
        return $this->findOneBy([
            'user' => $user,
            'event' => $event,
        ]);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function findByEvent(Event $event): array
    {
        return $this->findBy(['event' => $event]);
    }
}
