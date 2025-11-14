<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $perm_can_create_user = null;

    #[ORM\Column]
    private ?bool $perm_can_edit_user = null;

    #[ORM\Column]
    private ?bool $perm_can_read_user = null;

    #[ORM\Column]
    private ?bool $perm_can_delete_user = null;

    #[ORM\Column]
    private ?bool $perm_can_create_roles = null;

    #[ORM\Column]
    private ?bool $perm_can_edit_roles = null;

    #[ORM\Column]
    private ?bool $perm_can_read_roles = null;

    #[ORM\Column]
    private ?bool $perm_can_delete_roles = null;

    #[ORM\Column]
    private ?bool $perm_can_create_tasks = null;

    #[ORM\Column]
    private ?bool $perm_can_edit_tasks = null;

    #[ORM\Column]
    private ?bool $perm_can_read_all_tasks = null;

    #[ORM\Column]
    private ?bool $perm_can_delete_tasks = null;

    #[ORM\Column]
    private ?bool $perm_can_assign_tasks_to_user = null;

    #[ORM\Column]
    private ?bool $perm_can_assign_tasks_to_project = null;

    #[ORM\Column]
    private ?bool $perm_can_create_projects = null;

    #[ORM\Column]
    private ?bool $perm_can_edit_projects = null;

    #[ORM\Column]
    private ?bool $perm_can_read_projects = null;

    #[ORM\Column]
    private ?bool $perm_can_delete_projects = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
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

    public function isPermCanCrateUser(): ?bool
    {
        return $this->perm_can_create_user;
    }

    public function setPermCanCrateUser(bool $perm_can_create_user): static
    {
        $this->perm_can_create_user = $perm_can_create_user;
        return $this;
    }

    public function isPermCanEditUser(): ?bool
    {
        return $this->perm_can_edit_user;
    }

    public function setPermCanEditUser(bool $perm_can_edit_user): static
    {
        $this->perm_can_edit_user = $perm_can_edit_user;
        return $this;
    }

    public function isPermCanReadUser(): ?bool
    {
        return $this->perm_can_read_user;
    }

    public function setPermCanReadUser(bool $perm_can_read_user): static
    {
        $this->perm_can_read_user = $perm_can_read_user;
        return $this;
    }

    public function isPermCanDeleteUser(): ?bool
    {
        return $this->perm_can_delete_user;
    }

    public function setPermCanDeleteUser(bool $perm_can_delete_user): static
    {
        $this->perm_can_delete_user = $perm_can_delete_user;
        return $this;
    }

    public function isPermCanCreateRoles(): ?bool
    {
        return $this->perm_can_create_roles;
    }

    public function setPermCanCreateRoles(bool $perm_can_create_roles): static
    {
        $this->perm_can_create_roles = $perm_can_create_roles;
        return $this;
    }

    public function isPermCanEditRoles(): ?bool
    {
        return $this->perm_can_edit_roles;
    }

    public function setPermCanEditRoles(bool $perm_can_edit_roles): static
    {
        $this->perm_can_edit_roles = $perm_can_edit_roles;
        return $this;
    }

    public function isPermCanReadRoles(): ?bool
    {
        return $this->perm_can_read_roles;
    }

    public function setPermCanReadRoles(bool $perm_can_read_roles): static
    {
        $this->perm_can_read_roles = $perm_can_read_roles;
        return $this;
    }

    public function isPermCanDeleteRoles(): ?bool
    {
        return $this->perm_can_delete_roles;
    }

    public function setPermCanDeleteRoles(bool $perm_can_delete_roles): static
    {
        $this->perm_can_delete_roles = $perm_can_delete_roles;
        return $this;
    }

    public function isPermCanCreateTasks(): ?bool
    {
        return $this->perm_can_create_tasks;
    }

    public function setPermCanCreateTasks(bool $perm_can_create_tasks): static
    {
        $this->perm_can_create_tasks = $perm_can_create_tasks;
        return $this;
    }

    public function isPermCanEditTasks(): ?bool
    {
        return $this->perm_can_edit_tasks;
    }

    public function setPermCanEditTasks(bool $perm_can_edit_tasks): static
    {
        $this->perm_can_edit_tasks = $perm_can_edit_tasks;
        return $this;
    }

    public function isPermCanReadAllTasks(): ?bool
    {
        return $this->perm_can_read_all_tasks;
    }

    public function setPermCanReadAllTasks(bool $perm_can_read_all_tasks): static
    {
        $this->perm_can_read_all_tasks = $perm_can_read_all_tasks;
        return $this;
    }

    public function isPermCanDeleteTasks(): ?bool
    {
        return $this->perm_can_delete_tasks;
    }

    public function setPermCanDeleteTasks(bool $perm_can_delete_tasks): static
    {
        $this->perm_can_delete_tasks = $perm_can_delete_tasks;
        return $this;
    }

    public function isPermCanAssignTasksToUser(): ?bool
    {
        return $this->perm_can_assign_tasks_to_user;
    }

    public function setPermCanAssignTasksToUser(bool $perm_can_assign_tasks_to_user): static
    {
        $this->perm_can_assign_tasks_to_user = $perm_can_assign_tasks_to_user;
        return $this;
    }

    public function isPermCanAssignTasksToProject(): ?bool
    {
        return $this->perm_can_assign_tasks_to_project;
    }

    public function setPermCanAssignTasksToProject(bool $perm_can_assign_tasks_to_project): static
    {
        $this->perm_can_assign_tasks_to_project = $perm_can_assign_tasks_to_project;
        return $this;
    }

    public function isPermCanCreateProjects(): ?bool
    {
        return $this->perm_can_create_projects;
    }

    public function setPermCanCreateProjects(bool $perm_can_create_projects): static
    {
        $this->perm_can_create_projects = $perm_can_create_projects;
        return $this;
    }

    public function isPermCanEditProjects(): ?bool
    {
        return $this->perm_can_edit_projects;
    }

    public function setPermCanEditProjects(bool $perm_can_edit_projects): static
    {
        $this->perm_can_edit_projects = $perm_can_edit_projects;
        return $this;
    }

    public function isPermCanReadProjects(): ?bool
    {
        return $this->perm_can_read_projects;
    }

    public function setPermCanReadProjects(bool $perm_can_read_projects): static
    {
        $this->perm_can_read_projects = $perm_can_read_projects;
        return $this;
    }

    public function isPermCanDeleteProjects(): ?bool
    {
        return $this->perm_can_delete_projects;
    }

    public function setPermCanDeleteProjects(bool $perm_can_delete_projects): static
    {
        $this->perm_can_delete_projects = $perm_can_delete_projects;

        return $this;
    }
}
