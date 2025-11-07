<?php

declare(strict_types=1);

namespace App\Dev\DataGenerator;

use App\Entity\AppConfig;
use App\Entity\Project;
use App\Entity\Role;
use App\Entity\Task;
use App\Entity\User;
use App\Security\Permission\PermissionRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RandomDataSeeder
{
    private const ROLE_PROFILES = [
        'admin' => [
            'perm_can_create_user' => true,
            'perm_can_edit_user' => true,
            'perm_can_read_user' => true,
            'perm_can_delete_user' => true,
            'perm_can_create_tasks' => true,
            'perm_can_edit_tasks' => true,
            'perm_can_read_all_tasks' => true,
            'perm_can_delete_tasks' => true,
            'perm_can_assign_tasks_to_user' => true,
            'perm_can_assign_tasks_to_project' => true,
            'perm_can_create_projects' => true,
            'perm_can_edit_projects' => true,
            'perm_can_read_projects' => true,
            'perm_can_delete_projects' => true,
        ],
        'teamlead' => [
            'perm_can_create_user' => false,
            'perm_can_edit_user' => false,
            'perm_can_read_user' => true,
            'perm_can_delete_user' => false,
            'perm_can_create_tasks' => true,
            'perm_can_edit_tasks' => true,
            'perm_can_read_all_tasks' => true,
            'perm_can_delete_tasks' => false,
            'perm_can_assign_tasks_to_user' => true,
            'perm_can_assign_tasks_to_project' => true,
            'perm_can_create_projects' => true,
            'perm_can_edit_projects' => true,
            'perm_can_read_projects' => true,
            'perm_can_delete_projects' => false,
        ],
        'staff' => [
            'perm_can_create_user' => false,
            'perm_can_edit_user' => false,
            'perm_can_read_user' => false,
            'perm_can_delete_user' => false,
            'perm_can_create_tasks' => false,
            'perm_can_edit_tasks' => true,
            'perm_can_read_all_tasks' => false,
            'perm_can_delete_tasks' => false,
            'perm_can_assign_tasks_to_user' => false,
            'perm_can_assign_tasks_to_project' => false,
            'perm_can_create_projects' => false,
            'perm_can_edit_projects' => false,
            'perm_can_read_projects' => true,
            'perm_can_delete_projects' => false,
        ],
    ];

    private const ROLE_TARGET_COUNTS = [
        'admin' => 2,
        'teamlead' => 10,
        'staff' => 100,
    ];

    private const FIRST_NAMES = [
        'Alex', 'Sam', 'Jamie', 'Jordan', 'Taylor', 'Morgan', 'Casey', 'Dakota', 'Emerson', 'Harper',
        'Riley', 'Skyler', 'Avery', 'Logan', 'Rowan', 'Phoenix', 'Quinn', 'Peyton', 'Sawyer', 'Reese',
        'Charlie', 'Emery', 'Finley', 'Hayden', 'Kendall', 'Lennon', 'Parker', 'Sloane', 'Tatum', 'Wren',
    ];

    private const LAST_NAMES = [
        'Anderson', 'Bennett', 'Campbell', 'Dawson', 'Ellison', 'Fletcher', 'Grayson', 'Hampton', 'Iverson', 'Jensen',
        'Kensington', 'Lawson', 'Matthews', 'Nolan', 'Oakley', 'Prescott', 'Ramsey', 'Sterling', 'Thatcher', 'Underwood',
        'Vaughn', 'Whitaker', 'Xavier', 'Young', 'Zimmer', 'Holden', 'Carter', 'Dalton', 'Everly', 'Foster',
    ];

    private const PROJECT_PREFIXES = [
        'Apollo', 'Beacon', 'Cascade', 'Drift', 'Echo', 'Fusion', 'Harbor', 'Impulse', 'Jade', 'Keystone',
        'Lumen', 'Mosaic', 'Nimbus', 'Orbit', 'Pulse', 'Quantum', 'Radiant', 'Summit', 'Tango', 'Vertex',
        'Wave', 'Zenith', 'Aurora', 'Catalyst', 'Frontier', 'Horizon', 'Momentum', 'Nova', 'Phoenix', 'Voyager',
    ];

    private const PROJECT_SUFFIXES = [
        'Alpha', 'Bravo', 'Core', 'Drive', 'Edge', 'Flow', 'Gate', 'Hub', 'Insight', 'Launch',
        'Matrix', 'Nexus', 'Ops', 'Prime', 'Quest', 'Rise', 'Signal', 'Track', 'Unity', 'Vault',
        'Waypoint', 'Xchange', 'Yield', 'Zen', 'Forge', 'Loop', 'Sphere', 'Shift', 'Glow', 'Spark',
    ];

    private const TASK_TITLES = [
        'Prepare project brief', 'Draft user stories', 'Refine backlog', 'Implement authentication',
        'Design database schema', 'Review API contracts', 'Write integration tests', 'Polish UI copy',
        'Sync with stakeholders', 'Plan deployment pipeline', 'Configure monitoring', 'Document edge cases',
        'Analyse customer feedback', 'Prioritise bug fixes', 'Set up demo environment', 'Update coding guidelines',
        'Run exploratory testing', 'Evaluate feature toggle', 'Refactor legacy module', 'Audit access permissions',
        'Create onboarding checklist', 'Review security posture', 'Optimise database queries', 'Coordinate release notes',
    ];

    private const TASK_NOTES = [
        'Focus on keeping the experience lightweight.',
        'Coordinate with QA to ensure coverage before sign-off.',
        'Remember to loop in product marketing ahead of launch.',
        'Check compatibility with the mobile client before closing.',
        'Path the changes behind a feature flag for safe rollout.',
        'Gather quick feedback from at least two pilot teams.',
        'Document any assumptions around third-party integrations.',
        'Highlight potential blockers for the next sync.',
        'Pair with another teammate to speed up validation.',
        'Add findings to the shared knowledge base when done.',
    ];

    private const STATUSES = ['open', 'in_progress', 'review', 'done', 'cancelled'];
    private const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @return array{roles: int, users: int, projects: int, tasks: int}
     */
    public function seed(bool $purgeExisting = false): array
    {
        return $this->entityManager->wrapInTransaction(function () use ($purgeExisting) {
            if ($purgeExisting) {
                $this->purgeDatabase();
            } else {
                $this->assertDatabaseEmpty();
            }

            $this->ensureAppConfig();
            $roles = $this->createRoles();
            $usersByRole = $this->createUsers($roles);
            $projectOutcome = $this->createProjectsAndTasks($usersByRole);

            $this->entityManager->flush();

            return [
                'roles' => count($roles),
                'users' => array_reduce($usersByRole, static fn (int $carry, array $users) => $carry + count($users), 0),
                'projects' => count($projectOutcome['projects']),
                'tasks' => $projectOutcome['taskCount'],
            ];
        });
    }

    private function purgeDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('TRUNCATE TABLE image, logs, password_reset_tokens, task, project, user_to_role, role, "user", app_config RESTART IDENTITY CASCADE');
        $this->entityManager->clear();
    }

    private function assertDatabaseEmpty(): void
    {
        $connection = $this->entityManager->getConnection();
        $tables = [
            '"user"' => 'SELECT COUNT(*) FROM "user"',
            'role' => 'SELECT COUNT(*) FROM role',
            'project' => 'SELECT COUNT(*) FROM project',
            'task' => 'SELECT COUNT(*) FROM task',
            'app_config' => 'SELECT COUNT(*) FROM app_config',
        ];

        foreach ($tables as $label => $query) {
            $count = (int) $connection->fetchOne($query);
            if ($count > 0) {
                throw new \RuntimeException(sprintf('Table %s already contains data. Re-run with --purge to reset before seeding.', $label));
            }
        }
    }

    private function ensureAppConfig(): void
    {
        $repository = $this->entityManager->getRepository(AppConfig::class);
        if ($repository->count([]) > 0) {
            return;
        }

        $config = new AppConfig(['changeit.test']);
        $this->entityManager->persist($config);
    }

    /**
     * @return array<string, Role>
     */
    private function createRoles(): array
    {
        $catalog = [];
        foreach (self::ROLE_PROFILES as $slug => $permissions) {
            $role = new Role();
            foreach ($permissions as $permission => $value) {
                $getter = PermissionRegistry::MAP[$permission] ?? null;
                if ($getter === null) {
                    continue;
                }

                $setter = 'set' . substr($getter, 2);
                $role->$setter($value);
            }

            $catalog[$slug] = $role;
            $this->entityManager->persist($role);
        }

        return $catalog;
    }

    /**
     * @param array<string, Role> $roles
     * @return array<string, User[]>
     */
    private function createUsers(array $roles): array
    {
        $usersByRole = [];
        foreach (self::ROLE_TARGET_COUNTS as $roleSlug => $count) {
            $usersByRole[$roleSlug] = [];
            for ($i = 0; $i < $count; $i++) {
                $user = new User();

                $nameToken = $this->generateNameToken($roleSlug, $i);
                $user->setName($nameToken['display']);
                $user->setEmail($nameToken['email']);

                $password = $this->passwordHasher->hashPassword($user, 'Password123!');
                $user->setPassword($password);
                $user->setIsPasswordTemporary(false);
                $user->setActive(true);

                $createdAt = $this->randomPastDate(300);
                $user->setCreatedAt($createdAt);
                $user->setTemporaryPasswordCreatedAt($createdAt);

                if (random_int(0, 1) === 1) {
                    $user->setLastLoginAt(\DateTime::createFromImmutable($createdAt->modify(sprintf('+%d days', random_int(1, 60)))));
                }

                $user->assignRole($roles[$roleSlug]);

                $this->entityManager->persist($user);
                $usersByRole[$roleSlug][] = $user;
            }
        }

        return $usersByRole;
    }

    /**
     * @param array<string, User[]> $usersByRole
     * @return array{projects: Project[], taskCount: int}
     */
    private function createProjectsAndTasks(array $usersByRole): array
    {
        $projects = [];
        $taskCount = 0;

        $projectTaskAllocations = $this->buildTaskAllocation(count: 100);

        foreach ($projectTaskAllocations as $index => $taskTotal) {
            $project = new Project();
            $project->setName($this->generateProjectName($index));
            $project->setDescription($this->randomProjectDescription());

            $creatorPool = array_merge($usersByRole['admin'], $usersByRole['teamlead']);
            $project->setCreatedByUser($this->pickRandom($creatorPool));

            $createdAt = $this->randomPastDate(240);
            $project->setCreatedAt($createdAt);

            $this->entityManager->persist($project);
            $projects[] = $project;

            $taskCount += $this->createTasksForProject($project, $taskTotal, $usersByRole['teamlead'], $usersByRole['staff'], $createdAt);
        }

        return ['projects' => $projects, 'taskCount' => $taskCount];
    }

    /**
     * @param User[] $creators
     * @param User[] $assignees
     */
    private function createTasksForProject(Project $project, int $taskTotal, array $creators, array $assignees, \DateTimeImmutable $projectCreatedAt): int
    {
        if ($taskTotal === 0) {
            return 0;
        }

        $created = 0;
        for ($i = 0; $i < $taskTotal; $i++) {
            $task = new Task();
            $task->setProject($project);
            $task->setTitle($this->generateTaskTitle($project->getName(), $i));
            $task->setDescription($this->maybePick(self::TASK_NOTES));
            $task->setStatus($this->pickRandom(self::STATUSES));
            $task->setPriority($this->pickRandom(self::PRIORITIES));

            $createdAt = $this->randomTaskCreationDate($projectCreatedAt);
            $task->setCreatedAt($createdAt);
            $task->setCreatedByUser($this->pickRandom($creators));

            if (random_int(0, 100) < 85) {
                $task->setAssignedToUser($this->pickRandom($assignees));
            }

            if (random_int(0, 100) < 70) {
                $task->setDueDate($this->randomDueDate($createdAt));
            }

            if (random_int(0, 100) < 60) {
                $task->setUpdatedAt($this->randomUpdateDate($createdAt));
            }

            $this->entityManager->persist($task);
            $created++;
        }

        return $created;
    }

    private function buildTaskAllocation(int $count): array
    {
        $allocations = [0, 50];
        for ($i = 2; $i < $count; $i++) {
            $allocations[] = random_int(0, 50);
        }
        shuffle($allocations);

        return $allocations;
    }

    private function generateNameToken(string $roleSlug, int $index): array
    {
        $first = $this->pickRandom(self::FIRST_NAMES);
        $last = $this->pickRandom(self::LAST_NAMES);

        $suffix = strtoupper(substr($roleSlug, 0, 1));
        $display = sprintf('%s %s %s%02d', $first, $last, $suffix, $index + 1);
        $display = substr($display, 0, 32);

        $emailLocal = strtolower(sprintf('%s.%s.%s%02d', $first, $last, $suffix, $index + 1));
        $emailLocal = preg_replace('/[^a-z0-9\.]+/', '', $emailLocal) ?? 'user';
        $email = sprintf('%s@changeit.test', $emailLocal);

        return [
            'display' => $display,
            'email' => $email,
        ];
    }

    private function generateProjectName(int $index): string
    {
        $prefix = $this->pickRandom(self::PROJECT_PREFIXES);
        $suffix = $this->pickRandom(self::PROJECT_SUFFIXES);
        return sprintf('%s %s %03d', $prefix, $suffix, $index + 1);
    }

    private function randomProjectDescription(): string
    {
        $snippets = [
            'Focuses on improving operational efficiency across teams.',
            'Targets a better onboarding experience for new customers.',
            'Aims to consolidate legacy tooling into a unified workflow.',
            'Explores automation opportunities to reduce manual effort.',
            'Prepares the foundation for upcoming mobile app work.',
            'Builds alignment between design, product, and engineering.',
            'Delivers insights dashboards requested by leadership.',
            'Evaluates a new integration required for enterprise clients.',
            'Hardens core authentication paths for external audits.',
            'Introduces streamlined collaboration rituals for squads.',
        ];

        return $this->pickRandom($snippets);
    }

    private function generateTaskTitle(string $projectName, int $index): string
    {
        $base = $this->pickRandom(self::TASK_TITLES);
        return sprintf('%s – %s #%d', $projectName, $base, $index + 1);
    }

    private function randomPastDate(int $maxDays): \DateTimeImmutable
    {
        $daysAgo = random_int(0, $maxDays);
        $date = new \DateTimeImmutable(sprintf('-%d days', $daysAgo));
        return $date->setTime(random_int(7, 18), random_int(0, 59));
    }

    private function randomTaskCreationDate(\DateTimeImmutable $projectCreatedAt): \DateTimeImmutable
    {
        $now = new \DateTimeImmutable();
        $baseline = $projectCreatedAt > $now ? $now : $projectCreatedAt;
        $daysRange = max(0, $baseline->diff($now)->days ?? 0);
        $offsetDays = $daysRange > 0 ? random_int(0, $daysRange) : 0;
        $candidate = $projectCreatedAt->modify(sprintf('+%d days', $offsetDays));

        if ($candidate > $now) {
            $candidate = $now;
        }

        return $candidate->setTime(random_int(7, 19), random_int(0, 59));
    }

    private function randomDueDate(\DateTimeImmutable $createdAt): \DateTimeImmutable
    {
        $daysAhead = random_int(5, 120);
        $candidate = $createdAt->modify(sprintf('+%d days', $daysAhead));
        return $candidate->setTime(random_int(9, 17), random_int(0, 59));
    }

    private function randomUpdateDate(\DateTimeImmutable $createdAt): \DateTime
    {
        $now = new \DateTimeImmutable();
        $daysRange = max(1, $createdAt->diff($now)->days ?? 1);
        $offsetDays = random_int(1, $daysRange);
        $candidate = $createdAt->modify(sprintf('+%d days', $offsetDays));

        if ($candidate > $now) {
            $candidate = $now;
        }

        $candidate = $candidate->setTime(random_int(8, 19), random_int(0, 59));

        return \DateTime::createFromImmutable($candidate);
    }

    private function pickRandom(array $items)
    {
        return $items[array_rand($items)];
    }

    private function maybePick(array $items): ?string
    {
        if (random_int(0, 100) < 55) {
            return $this->pickRandom($items);
        }

        return null;
    }
}
