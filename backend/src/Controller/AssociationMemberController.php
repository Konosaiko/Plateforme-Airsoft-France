<?php

namespace App\Controller;

use App\Entity\Association;
use App\Entity\AssociationMember;
use App\Entity\User;
use App\Form\InviteMemberType;
use App\Service\AssociationMemberService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/association/{id}/members')]
class AssociationMemberController extends AbstractController
{
    private AssociationMemberService $associationMemberService;
    private EntityManagerInterface $entityManager;

    public function __construct(AssociationMemberService $associationMemberService, EntityManagerInterface $entityManager)
    {
        $this->associationMemberService = $associationMemberService;
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'app_association_members')]
    public function index(Association $association): Response
    {
        $this->denyAccessUnlessGranted('ASSOCIATION_VIEW', $association);

        return $this->render('association_member/index.html.twig', [
            'association' => $association,
            'members' => $association->getAssociationMembers(),
        ]);
    }

    #[Route('/invite', name: 'app_association_invite', methods: ['GET', 'POST'])]
    #[IsGranted('ASSOCIATION_MANAGE_MEMBERS', subject: 'association')]
    public function invite(Request $request, Association $association): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in to invite members.');
        }

        $form = $this->createForm(InviteMemberType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            try {
                $this->associationMemberService->inviteMember(
                    $association,
                    $data['user'],
                    $data['role'],
                    $user
                );
                $this->addFlash('success', 'Invitation sent successfully.');
                return $this->redirectToRoute('app_association_members', ['id' => $association->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('association_member/invite.html.twig', [
            'association' => $association,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{memberId}/change-role', name: 'app_association_change_role', methods: ['POST'])]
    #[IsGranted('ASSOCIATION_MANAGE_MEMBERS', subject: 'association')]
    public function changeRole(Request $request, Association $association, int $memberId): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in to change member roles.');
        }

        $member = $this->entityManager->getRepository(AssociationMember::class)->find($memberId);
        if (!$member || $member->getAssociation() !== $association) {
            throw $this->createNotFoundException('Member not found');
        }

        $newRole = $request->request->get('role');
        try {
            $this->associationMemberService->changeMemberRole($member, $newRole, $user);
            $this->addFlash('success', 'Member role updated successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_association_members', ['id' => $association->getId()]);
    }

    #[Route('/{memberId}/remove', name: 'app_association_remove_member', methods: ['POST'])]
    #[IsGranted('ASSOCIATION_MANAGE_MEMBERS', subject: 'association')]
    public function removeMember(Association $association, int $memberId): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in to remove members.');
        }

        $member = $this->entityManager->getRepository(AssociationMember::class)->find($memberId);
        if (!$member || $member->getAssociation() !== $association) {
            throw $this->createNotFoundException('Member not found');
        }

        try {
            $this->associationMemberService->removeMember($member, $user);
            $this->addFlash('success', 'Member removed successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_association_members', ['id' => $association->getId()]);
    }
}
