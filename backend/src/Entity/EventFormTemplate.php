<?php

namespace App\Entity;

use App\Repository\EventFormTemplateRepository;
use App\Entity\Association\Association;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventFormTemplateRepository::class)]
class EventFormTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Association::class, inversedBy: 'formTemplates')]
    #[ORM\JoinColumn(nullable: false)]
    private $association;

    #[ORM\Column(type: 'json')]
    private $fields = [];

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'formTemplate')]
    private $events;

    /**
     * @return mixed
     */
    public function getAssociation()
    {
        return $this->association;
    }

    /**
     * @param mixed $association
     */
    public function setAssociation($association): void
    {
        $this->association = $association;
    }

    public function getEvents(): ArrayCollection
    {
        return $this->events;
    }

    public function setEvents(ArrayCollection $events): void
    {
        $this->events = $events;
    }

    public function __construct()
    {
        $this->events = new ArrayCollection();
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

    public function getFields(): array
    {
        return array_map(function($field) {
            if (is_string($field)) {
                return json_decode($field, true);
            }
            return $field;
        }, $this->fields);
    }

    public function setFields($fields): self
    {
        $this->fields = array_map(function($field) {
            if (is_array($field)) {
                return json_encode($field);
            }
            return $field;
        }, $fields);
        return $this;
    }

    public function addField($field): self
    {
        if (is_array($field)) {
            $this->fields[] = json_encode($field);
        } else {
            $this->fields[] = $field;
        }
        return $this;
    }

    public function removeField(string $name): self
    {
        $this->fields = array_filter($this->fields, function($field) use ($name) {
            return $field['name'] !== $name;
        });
        return $this;
    }
}
