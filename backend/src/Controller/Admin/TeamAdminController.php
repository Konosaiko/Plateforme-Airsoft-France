<?php

namespace App\Controller\Admin;

use App\Entity\Team;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;


#[Route('/admin/team')]
#[IsGranted('ROLE_ADMIN')]
class TeamAdminController extends AbstractController
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }


    #[Route('/pending-list', name: 'app_admin_team_pending')]
    public function pendingTeams(EntityManagerInterface $entityManager): Response
    {
        $pendingTeams = $entityManager->getRepository(Team::class)->findBy(['status' => 'pending']);

        return $this->render('admin/team/pending.html.twig', [
            'pendingTeams' => $pendingTeams,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_admin_team_approve', methods: ['POST'])]
    public function approveTeam(EntityManagerInterface $entityManager, Team $team): Response
    {
        $team->approve();
        $entityManager->flush();

        $this->notificationService->createNotification(
            $team->getCreator(),
            "Your team '{$team->getName()}' has been approved.",
        );

        $this->addFlash('success', 'Team approved successfully.');
        return $this->redirectToRoute('app_admin_team_pending');
    }

    #[Route('/{id}/reject', name: 'app_admin_team_reject', methods: ['POST'])]
    public function rejectTeam(Request $request, EntityManagerInterface $entityManager, Team $team): Response
    {
        $rejectionReason = $request->request->get('rejection_reason');

        if (empty($rejectionReason)) {
            $this->addFlash('error', 'A reason for rejection is required.');
            return $this->redirectToRoute('app_admin_team_pending');
        }

        $team->reject();
        $team->setRejectionReason($rejectionReason);
        $entityManager->flush();

        $this->notificationService->createNotification(
            $team->getCreator(),
            "Your team '{$team->getName()}' has been rejected. Reason: {$rejectionReason}"
        );

        $this->addFlash('success', 'Team rejected successfully.');
        return $this->redirectToRoute('app_admin_team_pending');
    }



}