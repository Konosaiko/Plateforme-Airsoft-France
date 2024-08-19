<?php

namespace App\Controller\Event;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Entity\User;
use App\Service\Event\EventRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/event-registration')]
class EventRegistrationController extends AbstractController
{
    private EventRegistrationService $eventRegistrationService;

    public function __construct(EventRegistrationService $eventRegistrationService)
    {
        $this->eventRegistrationService = $eventRegistrationService;
    }

    #[Route('/{id}/register', name: 'event_register', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function register(Event $event): Response
    {
        try {
            $user = $this->getUser();
            if(!$user instanceof User) {
                throw $this->createAccessDeniedException('User must be logged in.');
            }

            $registration = $this->eventRegistrationService->registerUserForEvent($user, $event);
            $this->addFlash('success', 'Your registration has been received, you will receive a notification once confirmed.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }
    }

    #[Route('/{id}/cancel', name: 'event_cancel_registration', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancelRegistration(EventRegistration $registration): Response
    {
        try {
            $this->eventRegistrationService->cancelRegistration($registration);
            $this->addFlash('success', 'Votre inscription a été annulée.');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('event_show', ['id' => $registration->getEvent()->getId()]);
    }

    #[Route('/user/registrations', name: 'user_registrations', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function userRegistrations(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User must be logged in.');
        }

        $registrations = $this->eventRegistrationService->getUserRegistrations($user);
        return $this->render('_event/event_registration/index.html.twig', [
            'registrations' => $registrations,
        ]);
    }
}
