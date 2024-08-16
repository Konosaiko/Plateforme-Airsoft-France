<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Form\TeamType;
use App\Service\TeamMemberService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TeamController extends AbstractController
{
    private $entityManager;
    private $teamMemberService;

    public function __construct(EntityManagerInterface $entityManager, TeamMemberService $teamMemberService)
    {
        $this->entityManager = $entityManager;
        $this->teamMemberService = $teamMemberService;
    }

    #[Route('/teams', name: 'app_teams_list', methods: ['GET'])]
    public function index(): Response
    {
        $teamRepository = $this->entityManager->getRepository(Team::class);

        $teams = $this->isGranted('ROLE_ADMIN')
            ? $teamRepository->findAll()
            : $teamRepository->findBy(['status' => 'approved']);

        return $this->render('team/index.html.twig', ['teams' => $teams]);
    }

    #[Route('/my-teams', name: 'app_my_teams')]
    public function myTeams(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('You must be logged in to view your teams.');
        }

        $teams = $this->entityManager->getRepository(Team::class)->findBy(['creator' => $user]);

        return $this->render('team/my_teams.html.twig', [
            'teams' => $teams,
        ]);
    }

    #[Route('/create', name: "app_team_create", methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): Response
    {
        $team = new Team();
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $team->setCreator($user);
            $team->setStatus('pending');
            $this->entityManager->persist($team);

            $teamMember = new TeamMember();
            $teamMember->setTeam($team);
            $teamMember->setUser($user);
            $teamMember->setRole(TeamMember::ROLE_LEAD);
            $teamMember->setStatus('approved');
            $this->entityManager->persist($teamMember);

            $this->entityManager->flush();

            $this->addFlash('success', 'Your team creation request has been submitted and is pending approval.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        return $this->render('team/create.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}/delete', name: 'app_team_delete', methods: ['POST'])]
    #[IsGranted('TEAM_DELETE', subject: 'team')]
    public function delete(Team $team): Response
    {
        $this->entityManager->remove($team);
        $this->entityManager->flush();

        $this->addFlash('success', 'Team has been deleted successfully.');

        return $this->redirectToRoute('app_teams_list');
    }

    #[Route('/team/{id}/edit', name: 'app_team_edit', methods: ['GET', 'POST'])]
    #[IsGranted('TEAM_EDIT', subject: 'team')]
    public function edit(Team $team, Request $request): Response
    {
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Team has been updated successfully.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        return $this->render('team/edit.html.twig', [
            'team' => $team,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/join', name: 'app_team_join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(Team $team): Response
    {
        try {
            $this->teamMemberService->createJoinRequest($team, $this->getUser());
            $this->addFlash('success', 'Your join request has been sent to the team creator.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
    }

    #[Route('/{id}/leave', name: 'app_team_leave', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(Team $team): Response
    {
        $user = $this->getUser();
        $teamMember = $this->entityManager->getRepository(TeamMember::class)->findOneBy([
            'team' => $team,
            'user' => $user,
        ]);

        if ($teamMember) {
            $this->entityManager->remove($teamMember);
            $this->entityManager->flush();
            $this->addFlash('success', 'You have successfully left the team.');
        } else {
            $this->addFlash('warning', 'You are not a member of this team.');
        }

        return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
    }

    #[Route('/{id}', name: 'app_team_show', methods: ['GET'])]
    public function show(Team $team): Response
    {
        return $this->render('team/show.html.twig', ['team' => $team]);
    }

    #[Route('/{id}/manage-requests', name: 'app_team_manage_requests')]
    #[IsGranted('TEAM_MANAGE', subject: 'team')]
    public function manageRequests(Team $team): Response
    {
        $pendingRequests = $this->teamMemberService->getPendingRequests($team);

        return $this->render('team/manage_requests.html.twig', [
            'team' => $team,
            'pendingRequests' => $pendingRequests,
        ]);
    }

    #[Route('/request/{id}/approve', name: 'app_team_request_approve', methods: ['POST'])]
    public function approveRequest(TeamMember $teamMember): Response
    {
        $this->denyAccessUnlessGranted('TEAM_MANAGE', $teamMember->getTeam());

        $this->teamMemberService->approveJoinRequest($teamMember);

        $this->addFlash('success', 'Join request approved.');
        return $this->redirectToRoute('app_team_manage_requests', ['id' => $teamMember->getTeam()->getId()]);
    }

    #[Route('/request/{id}/reject', name: 'app_team_request_reject', methods: ['POST'])]
    public function rejectRequest(TeamMember $teamMember): Response
    {
        $this->denyAccessUnlessGranted('TEAM_MANAGE', $teamMember->getTeam());

        $this->teamMemberService->rejectJoinRequest($teamMember);

        $this->addFlash('success', 'Join request rejected.');
        return $this->redirectToRoute('app_team_manage_requests', ['id' => $teamMember->getTeam()->getId()]);
    }
}
