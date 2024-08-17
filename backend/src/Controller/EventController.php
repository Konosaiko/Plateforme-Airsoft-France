<?php

namespace App\Controller;

use App\Entity\Event;
use App\Service\EventService;
use App\Form\EventType;
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
        return $this->render('event/index.html.twig', [
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

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
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

        return $this->render('event/edit.html.twig', [
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