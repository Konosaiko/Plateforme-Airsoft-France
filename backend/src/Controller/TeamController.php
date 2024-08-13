<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TeamMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TeamController extends AbstractController
{
    #[Route('/', name: 'app_teams_list', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $teams = $entityManager->getRepository(Team::class)->findAll();
        return $this->render('team/index.html.twig', [
            'teams' => $teams,
        ]);
    }

    #[Route('/{id}', name: 'app_team_show', methods: ['GET'])]
    public function show(Team $team): Response
    {
        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }

    #[Route('/{id}/join', name: 'app_team_join', methods: ['POST'])]
    public function join(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        // Check if user is already a member
        $existingMember = $entityManager->getRepository(TeamMember::class)->findOneBy([
            'team' => $team,
            'user' => $user,
        ]);

        if ($existingMember) {
            $this->addFlash('warning', 'You are already a member of this team.');
        } else {
            $teamMember = new TeamMember();
            $teamMember->setTeam($team);
            $teamMember->setUser($user);
            $entityManager->persist($teamMember);
            $entityManager->flush();

            $this->addFlash('success', 'You have successfully joined the team!');
        }

        return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
    }
}
