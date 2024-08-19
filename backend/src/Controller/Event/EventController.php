<?php

namespace App\Controller\Event;

use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\EventRegistrationRepository;
use App\Service\Event\EventRegistrationService;
use App\Service\Event\EventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/event')]
class EventController extends AbstractController
{
    private $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    #[Route('', name: 'event_index', methods: ['GET'])]
    public function index(): Response
    {
        $events = $this->eventService->getUpcomingEvents();
        return $this->render('_event/event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'event_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $event = new Event();
        $this->denyAccessUnlessGranted('create', $event);

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->eventService->createEvent($event, $event->getAssociation());
            $this->addFlash('success', 'Événement créé avec succès.');
            return $this->redirectToRoute('event_index');
        }

        return $this->render('_event/event/new.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'event_show', methods: ['GET'])]
    public function show(Event $event, EventRegistrationService $registrationService): Response
    {
        $userRegistration = null;
        $user = $this->getUser();
        if ($user instanceof User) {
            $userRegistration = $registrationService->getUserRegistrationForEvent($user, $event);
        }

        $activeRegistrationsCount = $registrationService->getActiveRegistrationsCount($event);

        return $this->render('_event/event/show.html.twig', [
            'event' => $event,
            'userRegistration' => $userRegistration,
            'activeRegistrationsCount' => $activeRegistrationsCount,
        ]);
    }

    #[Route('/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event): Response
    {
        $this->denyAccessUnlessGranted('edit', $event);

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->eventService->updateEvent($event);
            $this->addFlash('success', 'Événement mis à jour avec succès.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('_event/event/edit.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event): Response
    {
        $this->denyAccessUnlessGranted('delete', $event);

        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $this->eventService->deleteEvent($event);
            $this->addFlash('success', 'Événement supprimé avec succès.');
        }

        return $this->redirectToRoute('event_index');
    }
}