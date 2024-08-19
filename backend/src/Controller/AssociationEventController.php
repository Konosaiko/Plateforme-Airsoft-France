<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Repository\EventRegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/association/event')]
class AssociationEventController extends AbstractController
{
    #[Route('/{id}/pending-registrations', name: 'association_event_pending_registrations')]
    #[IsGranted('manage_registrations', subject: 'event')]
    public function pendingRegistrations(Event $event, EventRegistrationRepository $registrationRepository): Response
    {
        $pendingRegistrations = $registrationRepository->findPendingRegistrationsByEvent($event);

        return $this->render('association_event/index.html.twig', [
            'event' => $event,
            'pendingRegistrations' => $pendingRegistrations,
        ]);
    }

    #[Route('/registration/{id}/confirm', name: 'association_event_confirm_registration', methods: ['POST'])]

    public function confirmRegistration(EventRegistration $registration, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('manage_registrations', $registration->getEvent());
        $registration->setStatus('confirmed');
        $entityManager->flush();

        $this->addFlash('success', 'Inscription confirmée avec succès.');

        // ajouter notification à l'utilisateur

        return $this->redirectToRoute('association_event_pending_registrations', ['id' => $registration->getEvent()->getId()]);
    }

    #[Route('/registration/{id}/reject', name: 'association_event_reject_registration', methods: ['POST'])]
    public function rejectRegistration(
        Request $request,
        EventRegistration $registration,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('manage_registrations', $registration->getEvent());

        $rejectionReason = $request->request->get('rejection_reason');

        if (empty($rejectionReason)) {
            $this->addFlash('error', 'Une raison de rejet est requise.');
            return $this->redirectToRoute('association_event_pending_registrations', [
                'id' => $registration->getEvent()->getId()
            ]);
        }

        $registration->setStatus('rejected');
        $registration->setRejectionReason($rejectionReason);
        $entityManager->flush();

        $this->addFlash('success', 'Inscription rejetée avec succès.');

        return $this->redirectToRoute('association_event_pending_registrations', [
            'id' => $registration->getEvent()->getId()
        ]);
    }
}
