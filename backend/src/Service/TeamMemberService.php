<?php

namespace App\Service;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class TeamMemberService
{
    private $entityManager;
    private $notificationService;

    public function __construct(EntityManagerInterface $entityManager, NotificationService $notificationService)
    {
        $this->entityManager = $entityManager;
        $this->notificationService = $notificationService;
    }

    public function createJoinRequest(Team $team, User $user): TeamMember
    {
        $existingRequest = $this->entityManager->getRepository(TeamMember::class)->findOneBy([
            'team' => $team,
            'user' => $user
        ]);

        if ($existingRequest) {
            throw new \Exception('A request for this team already exists.');
        }

        $teamMember = new TeamMember();
        $teamMember->setTeam($team);
        $teamMember->setUser($user);

        $this->entityManager->persist($teamMember);
        $this->entityManager->flush();

        $this->notificationService->createNotification(
            $team->getCreator(),
            "New join request for team {$team->getName()} from user {$user->getUsername()}"
        );

        return $teamMember;
    }

    public function approveJoinRequest(TeamMember $teamMember): void
    {
        $teamMember->approve();
        $this->entityManager->flush();

        $this->notificationService->createNotification(
            $teamMember->getUser(),
            "Your request to join team {$teamMember->getTeam()->getName()} has been approved."
        );
    }

    public function rejectJoinRequest(TeamMember $teamMember): void
    {
        $teamMember->reject();
        $this->entityManager->flush();

        $this->notificationService->createNotification(
            $teamMember->getUser(),
            "Your request to join team {$teamMember->getTeam()->getName()} has been rejected."
        );
    }

    public function getPendingRequests(Team $team): array
    {
        return $this->entityManager->getRepository(TeamMember::class)->findBy([
            'team' => $team,
            'status' => 'pending'
        ]);
    }

    public function promoteToOfficer(TeamMember $teamMember): void
    {
        if ($teamMember->getRole() === TeamMember::ROLE_LEAD) {
            throw new \InvalidArgumentException("Cannot promote the leader");
        }

        $teamMember->setRole(TeamMember::ROLE_OFFICER);
        $this->entityManager->flush();

        $this->notificationService->createNotification(
            $teamMember->getUser(),
            "You have been promoted to Officer in team '{$teamMember->getTeam()->getName()}'."
        );
    }

    public function demoteToSoldier(TeamMember $teamMember): void
    {
        if ($teamMember->getRole() === TeamMember::ROLE_LEAD) {
            throw new \InvalidArgumentException("Cannot demote the leader");
        }

        $teamMember->setRole(TeamMember::ROLE_MEMBER);
        $this->entityManager->flush();

        $this->notificationService->createNotification(
            $teamMember->getUser(),
            "You have been demoted to solidier in team '{$teamMember->getTeam()->getName()}'."
        );
    }
}