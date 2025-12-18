<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $created_by_user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: Task::class)]
    private Collection $tasks;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: Image::class)]
    private Collection $images;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'project_team_leads')]
    private Collection $teamLeads;

    #[ORM\Column(options: ['default' => false])]
    private bool $is_completed = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completed_at = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $completed_by_user = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->created_at = new \DateTimeImmutable();
        $this->tasks = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->teamLeads = new ArrayCollection();
    }

    public function getId(): ?Uuid
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

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedByUser(): ?User
    {
        return $this->created_by_user;
    }

    public function setCreatedByUser(?User $created_by_user): static
    {
        $this->created_by_user = $created_by_user;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setProject($this);
        }
        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getProject() === $this) {
                $task->setProject(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProject($this);
        }
        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getProject() === $this) {
                $image->setProject(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getTeamLeads(): Collection
    {
        return $this->teamLeads;
    }

    public function addTeamLead(User $user): static
    {
        if (!$this->teamLeads->contains($user)) {
            $this->teamLeads->add($user);
        }

        return $this;
    }

    public function removeTeamLead(User $user): static
    {
        $this->teamLeads->removeElement($user);
        return $this;
    }

    public function isTeamLead(User $user): bool
    {
        return $this->teamLeads->contains($user);
    }

    public function isCompleted(): bool
    {
        return $this->is_completed;
    }

    public function setIsCompleted(bool $is_completed): static
    {
        $this->is_completed = $is_completed;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completed_at;
    }

    public function setCompletedAt(?\DateTimeInterface $completed_at): static
    {
        $this->completed_at = $completed_at;
        return $this;
    }

    public function getCompletedByUser(): ?User
    {
        return $this->completed_by_user;
    }

    public function setCompletedByUser(?User $completed_by_user): static
    {
        $this->completed_by_user = $completed_by_user;
        return $this;
    }
}
