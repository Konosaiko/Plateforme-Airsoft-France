<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NotificationController extends AbstractController
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }


    #[Route('/notifications', name: 'app_notifications')]
    public function index(): Response
    {
        $user = $this->getUser();
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('User must be logged in.');
        }
        $notifications = $this->notificationService->getAllNotifications($user);



        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notifications/{id}/mark-as-read', name: 'app_notification_mark_as_read', methods: ['POST'])]
    public function markAsRead(Notification $notification): Response
    {
        $this->notificationService->markAsRead($notification);

        return $this->redirectToRoute('app_notifications');
    }
}
