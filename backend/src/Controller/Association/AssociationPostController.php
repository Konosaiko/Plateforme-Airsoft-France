<?php

namespace App\Controller\Association;

use App\Entity\Association\Association;
use App\Entity\Association\AssociationPost;
use App\Entity\User;
use App\Form\Association\AssociationPostType;
use App\Service\Association\AssociationPostService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/association/{id}/posts')]
class AssociationPostController extends AbstractController
{
    private $postService;
    private $entityManager;

    public function __construct(AssociationPostService $postService, EntityManagerInterface $entityManager)
    {
        $this->postService = $postService;
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'app_association_posts', methods: ['GET'])]
    public function index(Association $association): Response
    {
        $posts = $this->postService->getPostsForAssociation($association);
        return $this->render('_association/association_post/index.html.twig', [
            'association' => $association,
            'posts' => $posts,
        ]);
    }

    #[Route('/new', name: 'app_association_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('ASSOCIATION_EDIT', subject: 'association')]
    public function new(Request $request, Association $association): Response
    {
        $form = $this->createForm(AssociationPostType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('You must be logged in to create a post.');
            }

            $data = $form->getData();
            $this->postService->createPost(
                $association,
                $user,
                $data['title'],
                $data['content']
            );
            $this->addFlash('success', 'Post created successfully.');
            return $this->redirectToRoute('app_association_posts', ['id' => $association->getId()]);
        }

        return $this->render('_association/association_post/new.html.twig', [
            'association' => $association,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{postId}', name: 'app_association_post_show', methods: ['GET'])]
    public function show(Association $association, int $postId): Response
    {
        $post = $this->entityManager->getRepository(AssociationPost::class)->find($postId);
        if (!$post || $post->getAssociation() !== $association) {
            throw $this->createNotFoundException('Post not found');
        }

        return $this->render('association_post/show.html.twig', [
            'association' => $association,
            'post' => $post,
        ]);
    }

    #[Route('/{postId}/edit', name: 'app_association_post_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ASSOCIATION_EDIT', subject: 'association')]
    public function edit(Request $request, Association $association, int $postId): Response
    {
        $post = $this->entityManager->getRepository(AssociationPost::class)->find($postId);
        if (!$post || $post->getAssociation() !== $association) {
            throw $this->createNotFoundException('Post not found');
        }

        $form = $this->createForm(AssociationPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->postService->updatePost($post, $form->get('title')->getData(), $form->get('content')->getData());
            $this->addFlash('success', 'Post updated successfully.');
            return $this->redirectToRoute('app_association_post_show', ['id' => $association->getId(), 'postId' => $post->getId()]);
        }

        return $this->render('_association/association_post/edit.html.twig', [
            'association' => $association,
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{postId}', name: 'app_association_post_delete', methods: ['POST'])]
    #[IsGranted('ASSOCIATION_EDIT', subject: 'association')]
    public function delete(Request $request, Association $association, int $postId): Response
    {
        $post = $this->entityManager->getRepository(AssociationPost::class)->find($postId);
        if (!$post || $post->getAssociation() !== $association) {
            throw $this->createNotFoundException('Post not found');
        }

        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $this->postService->deletePost($post);
            $this->addFlash('success', 'Post deleted successfully.');
        }

        return $this->redirectToRoute('app_association_posts', ['id' => $association->getId()]);
    }
}
