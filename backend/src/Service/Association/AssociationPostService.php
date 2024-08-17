<?php

namespace App\Service\Association;

use App\Entity\Association\Association;
use App\Entity\Association\AssociationPost;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AssociationPostService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createPost(Association $association, User $author, string $title, string $content): AssociationPost
    {
        $post = new AssociationPost();
        $post->setAssociation($association);
        $post->setAuthor($author);
        $post->setTitle($title);
        $post->setContent($content);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    public function updatePost(AssociationPost $post, string $title, string $content): AssociationPost
    {
        $post->setTitle($title);
        $post->setContent($content);
        $post->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $post;
    }

    public function deletePost(AssociationPost $post): void
    {
        $this->entityManager->remove($post);
        $this->entityManager->flush();
    }

    public function getPostsForAssociation(Association $association): array
    {
        return $this->entityManager->getRepository(AssociationPost::class)->findBy(
            ['association' => $association],
            ['createdAt' => 'DESC']
        );
    }
}