<?php

namespace App\Service\Event;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Entity\User;
use App\Repository\EventRegistrationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use SebastianBergmann\Type\RuntimeException;
use Symfony\Component\Finder\Exception\AccessDeniedException;

class EventRegistrationService
{
    private EntityManagerInterface $entityManager;
    private EventRegistrationRepository $eventRegistrationRepository;

    private NotificationService $notificationService;

    public function __construct(EntityManagerInterface $entityManager, EventRegistrationRepository $eventRegistrationRepository, NotificationService $notificationService)
    {
        $this->entityManager = $entityManager;
        $this->eventRegistrationRepository = $eventRegistrationRepository;
        $this->notificationService = $notificationService;
    }

    public function registerUserForEvent(User $user, Event $event): EventRegistration
    {
        if (!$event->canRegister()) {
            throw new \LogicException('Registration is not possible for this event.');
        }

        $registration = new EventRegistration();
        $registration->setUser($user);
        $registration->setEvent($event);

        if ($event->getAvailableSpots() === 0) {
            $registration->setStatus('waitlist');
        } else {
            $registration->setStatus('pending');
        }

        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        $this->notificationService->notifyEventRegistrationStatusChange($registration);

        return $registration;
    }


    public function cancelRegistration(EventRegistration $registration): void
    {
        $registration->setStatus('cancelled');
        $this->entityManager->flush();

        $this->notificationService->notifyEventRegistrationStatusChange($registration);
    }

    public function confirmRegistration(EventRegistration $registration): void
    {
        $registration->setStatus('confirmed');
        $this->entityManager->flush();

        $this->notificationService->notifyEventRegistrationStatusChange($registration);

        $this->checkWaitlist($registration->getEvent());
    }

    private function isUserRegisteredForEvent(User $user, Event $event): bool
    {
        return $this->entityManager->getRepository(EventRegistration::class)->findOneBy([
                'user' => $user,
                'event' => $event,
            ]) !== null;
    }

    private function checkWaitlist(Event $event): void
    {
        if ($event->getAvailableSpots() > 0) {
            $waitlistRegistration = $this->eventRegistrationRepository->findOneBy([
                'event' => $event,
                'status' => 'waitlist'
            ], ['createdAt' => 'ASC']);

            if ($waitlistRegistration) {
                $this->confirmRegistration($waitlistRegistration);
            }
        }
    }

    public function getEventRegistrations(Event $event): array
    {
        return $this->eventRegistrationRepository->findBy(['event' => $event]);
    }

    public function getActiveRegistrationsCount(Event $event): int
    {
        return $this->eventRegistrationRepository->countActiveRegistrations($event);
    }

    public function getUserRegistrations(User $user): array
    {
        return $this->eventRegistrationRepository->findBy(['user' => $user]);
    }

    public function getUserRegistrationForEvent(User $user, Event $event): ?EventRegistration
    {
        return $this->eventRegistrationRepository->findOneBy([
            'user' => $user,
            'event' => $event,
        ]);
    }
}