<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class RegistrationStatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EventRegistration::class, inversedBy: 'statusHistory')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EventRegistration $registration = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $oldStatus = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $newStatus = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $changedAt = null;

    public function getChangedAt(): ?\DateTimeInterface
    {
        return $this->changedAt;
    }

    public function setChangedAt(?\DateTimeInterface $changedAt): void
    {
        $this->changedAt = $changedAt;
    }

    public function getNewStatus(): ?string
    {
        return $this->newStatus;
    }

    public function setNewStatus(?string $newStatus): void
    {
        $this->newStatus = $newStatus;
    }

    public function getOldStatus(): ?string
    {
        return $this->oldStatus;
    }

    public function setOldStatus(?string $oldStatus): void
    {
        $this->oldStatus = $oldStatus;
    }

    public function getRegistration(): ?EventRegistration
    {
        return $this->registration;
    }

    public function setRegistration(?EventRegistration $registration): void
    {
        $this->registration = $registration;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }


}