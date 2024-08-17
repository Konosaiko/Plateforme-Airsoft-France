<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Association\Association;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class EventService
{
    private $entityManager;
    private $eventRepository;
    private $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;
        $this->security = $security;
    }

    public function createEvent(Event $event, Association $association): Event
    {
        if (!$this->security->isGranted('create', $event)) {
            throw new AccessDeniedException('You do not have permission to create events for this association.');
        }

        $event->setAssociation($association);
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        // Logique supplémentaire après la mise à jour de l'événement (notification à ajouter)
        return $event;
    }

    public function updateEvent(Event $event): Event
    {
        if (!$this->security->isGranted('edit', $event)) {
            throw new AccessDeniedException('You do not have permission to edit this event.');
        }

        $this->entityManager->flush();

        // Logique supplémentaire après la mise à jour de l'événement (notification à ajouter)
        return $event;
    }

    public function deleteEvent(Event $event): void
    {
        if (!$this->security->isGranted('delete', $event)) {
            throw new AccessDeniedException('You do not have permission to delete this event.');
        }

        $this->entityManager->remove($event);
        $this->entityManager->flush();

        // Logique supplémentaire après la mise à jour de l'événement (notification à ajouter)
    }

    public function getUpcomingEvents(): array
    {
        return $this->eventRepository->findUpcomingEvents();
    }


}