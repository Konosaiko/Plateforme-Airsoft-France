<?php

namespace App\Service;

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
}