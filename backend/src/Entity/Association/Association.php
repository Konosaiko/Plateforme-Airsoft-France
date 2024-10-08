<?php

namespace App\Entity\Association;

use App\Entity\EventFormTemplate;
use App\Entity\User;
use App\Repository\AssociationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssociationRepository::class)]
class Association
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, AssociationMember>
     */
    #[ORM\OneToMany(targetEntity: AssociationMember::class, mappedBy: 'association')]
    private Collection $associationMembers;

    /**
     * @var Collection<int, AssociationPost>
     */
    #[ORM\OneToMany(targetEntity: AssociationPost::class, mappedBy: 'association')]
    private Collection $associationPosts;

    #[ORM\OneToMany(targetEntity: EventFormTemplate::class, mappedBy: 'association', orphanRemoval: true)]
    private Collection $formTemplates;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->associationMembers = new ArrayCollection();
        $this->associationPosts = new ArrayCollection();
        $this->formTemplates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, AssociationMember>
     */
    public function getAssociationMembers(): Collection
    {
        return $this->associationMembers;
    }

    public function addAssociationMember(AssociationMember $associationMember): static
    {
        if (!$this->associationMembers->contains($associationMember)) {
            $this->associationMembers->add($associationMember);
            $associationMember->setAssociation($this);
        }

        return $this;
    }

    public function removeAssociationMember(AssociationMember $associationMember): static
    {
        if ($this->associationMembers->removeElement($associationMember)) {
            // set the owning side to null (unless already changed)
            if ($associationMember->getAssociation() === $this) {
                $associationMember->setAssociation(null);
            }
        }

        return $this;
    }

    public function getOwner(): ?User
    {
        foreach ($this->associationMembers as $member) {
            if ($member->isOwner()) {
                return $member->getUser();
            }
        }
        return null;
    }

    /**
     * @return Collection<int, AssociationPost>
     */
    public function getAssociationPosts(): Collection
    {
        return $this->associationPosts;
    }

    public function addAssociationPost(AssociationPost $associationPost): static
    {
        if (!$this->associationPosts->contains($associationPost)) {
            $this->associationPosts->add($associationPost);
            $associationPost->setAssociation($this);
        }

        return $this;
    }

    public function removeAssociationPost(AssociationPost $associationPost): static
    {
        if ($this->associationPosts->removeElement($associationPost)) {
            // set the owning side to null (unless already changed)
            if ($associationPost->getAssociation() === $this) {
                $associationPost->setAssociation(null);
            }
        }

        return $this;
    }

    public function getFormTemplates(): Collection
    {
        return $this->formTemplates;
    }

    public function addFormTemplate(EventFormTemplate $formTemplate): self
    {
        if (!$this->formTemplates->contains($formTemplate)) {
            $this->formTemplates->add($formTemplate);
            $formTemplate->setAssociation($this);
        }

        return $this;
    }

    public function removeFormTemplate(EventFormTemplate $formTemplate): self
    {
        if ($this->formTemplates->removeElement($formTemplate)) {
            if ($formTemplate->getAssociation() === $this) {
                $formTemplate->setAssociation(null);
            }
        }

        return $this;
    }
}
