<?php

namespace App\Service;

use App\Entity\EventRegistration;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createNotification(User $user, string $message): void
    {
        $notification = new Notification($user);
        $notification->setUser($user);
        $notification->setMessage($message);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function getAllNotifications(User $user): array
    {
        return $this->entityManager->getRepository(Notification::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->setRead(true);
        $this->entityManager->flush();
    }

    public function notifyEventRegistrationStatusChange(EventRegistration $registration): void
    {
        $user = $registration->getUser();
        $event = $registration->getEvent();
        $status = $registration->getStatus();

        $message = $this->getMessageForStatus($event->getTitle(), $status);

        $this->createNotification($user, $message);
    }

    private function getMessageForStatus(string $eventTitle, string $status): string
    {
        return match ($status) {
            'pending' => "Votre inscription à l'événement '$eventTitle' est en attente de confirmation.",
            'confirmed' => "Votre inscription à l'événement '$eventTitle' a été confirmée.",
            'rejected' => "Votre inscription à l'événement '$eventTitle' a été rejetée.",
            'waitlist' => "Vous êtes sur la liste d'attente pour l'événement '$eventTitle'.",
            'cancelled' => "Votre inscription à l'événement '$eventTitle' a été annulée.",
            default => "Le statut de votre inscription à l'événement '$eventTitle' a changé.",
        };
    }
}