<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Form\TeamType;
use App\Security\Voter\TeamVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

    #[Route('/create', name: "app_team_create", methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $team = new Team();
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team->setCreator($this->getUser());
            $entityManager->persist($team);
            $entityManager->flush();

            $this->addFlash('success', 'Team has been created.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        return $this->render('team/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_team_show', methods: ['GET'])]
    public function show(Team $team): Response
    {
        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }


    #[Route('/{id}/delete', name: 'app_team_delete', methods: ['POST'])]
    #[IsGranted('TEAM_DELETE', subject: 'team')]
    public function delete(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($team);
        $entityManager->flush();

        $this->addFlash('success', 'Team has been deleted successfully.');

        return $this->redirectToRoute('app_teams_list');
    }

    #[Route('/team/{id}/edit', name: 'app_team_edit', methods: ['GET', 'POST'])]
    #[IsGranted('TEAM_EDIT', subject: 'team')]
    public function edit(Team $team, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();
            $this->addFlash('success', 'Team has been updated successfully.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }
        return $this->render('team/edit.html.twig', [
            'team' => $team,
            'form' => $form->createView(),
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

    #[Route('/{id}/leave', name: 'app_team_leave', methods: ['POST'])]
    public function leave(Team $team, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        $teamMember = $entityManager->getRepository(TeamMember::class)->findOneBy([
            'team' => $team,
            'user' => $user,
        ]);

        if ($teamMember) {
            $entityManager->remove($teamMember);
            $entityManager->flush();

            $this->addFlash('success', 'You have successfully left the team.');
        } else {
            $this->addFlash('warning', 'You are not a member of this team.');
        }

        return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
    }
}
