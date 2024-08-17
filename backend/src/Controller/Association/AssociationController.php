<?php

namespace App\Controller\Association;

use App\Entity\Association\Association;
use App\Entity\User;
use App\Form\Association\AssociationType;
use App\Service\Association\AssociationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/association')]
class AssociationController extends AbstractController
{
    private AssociationService $associationService;

    public function __construct(AssociationService $associationService)
    {
        $this->associationService = $associationService;
    }

    #[Route('', name: 'app_association_index', methods: ['GET'])]
    public function index(): Response
    {
        $associations = $this->associationService->getAllAssociations();
        return $this->render('_association/association/index.html.twig', [
            'associations' => $associations,
        ]);
    }

    #[Route('/new', name: 'app_association_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in to create an association.');
        }

        $association = new Association();
        $form = $this->createForm(AssociationType::class, $association);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $association = $this->associationService->createAssociation($association, $user);
            $this->addFlash('success', 'Association created successfully.');
            return $this->redirectToRoute('app_association_show', ['id' => $association->getId()]);
        }

        return $this->render('_association/association/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_association_show', methods: ['GET'])]
    public function show(Association $association): Response
    {
        return $this->render('_association/association/show.html.twig', [
            'association' => $association,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_association_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ASSOCIATION_EDIT', subject: 'association')]
    public function edit(Request $request, Association $association): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in to edit an association.');
        }

        $form = $this->createForm(AssociationType::class, $association);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->associationService->updateAssociation($association, $user);
            $this->addFlash('success', 'Association updated successfully.');
            return $this->redirectToRoute('app_association_show', ['id' => $association->getId()]);
        }

        return $this->render('_association/association/edit.html.twig', [
            'association' => $association,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_association_delete', methods: ['POST'])]
    #[IsGranted('ASSOCIATION_DELETE', subject: 'association')]
    public function delete(Request $request, Association $association): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in to delete an association.');
        }

        if ($this->isCsrfTokenValid('delete'.$association->getId(), $request->request->get('_token'))) {
            $this->associationService->deleteAssociation($association, $user);
            $this->addFlash('success', 'Association deleted successfully.');
        }

        return $this->redirectToRoute('app_association_index');
    }
}
