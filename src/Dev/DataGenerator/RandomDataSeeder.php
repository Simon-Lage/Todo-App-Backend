<?php

declare(strict_types=1);

namespace App\Dev\DataGenerator;

use App\Entity\Image;
use App\Entity\Permission;
use App\Entity\Project;
use App\Entity\Role;
use App\Entity\Task;
use App\Entity\User;
use App\Image\Service\ImageStorage;
use App\Security\Permission\PermissionEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RandomDataSeeder
{
    private const ROLE_PROFILES = [
        'admin' => [
            PermissionEnum::CAN_CREATE_USER,
            PermissionEnum::CAN_EDIT_USER,
            PermissionEnum::CAN_READ_USER,
            PermissionEnum::CAN_DELETE_USER,
            PermissionEnum::CAN_CREATE_ROLES,
            PermissionEnum::CAN_EDIT_ROLES,
            PermissionEnum::CAN_READ_ROLES,
            PermissionEnum::CAN_DELETE_ROLES,
            PermissionEnum::CAN_CREATE_TASKS,
            PermissionEnum::CAN_EDIT_TASKS,
            PermissionEnum::CAN_READ_ALL_TASKS,
            PermissionEnum::CAN_DELETE_TASKS,
            PermissionEnum::CAN_ASSIGN_TASKS_TO_USER,
            PermissionEnum::CAN_ASSIGN_TASKS_TO_PROJECT,
            PermissionEnum::CAN_CREATE_PROJECTS,
            PermissionEnum::CAN_EDIT_PROJECTS,
            PermissionEnum::CAN_READ_PROJECTS,
            PermissionEnum::CAN_DELETE_PROJECTS,
        ],
        'teamlead' => [
            PermissionEnum::CAN_READ_USER,
            PermissionEnum::CAN_READ_ROLES,
            PermissionEnum::CAN_CREATE_TASKS,
            PermissionEnum::CAN_EDIT_TASKS,
            PermissionEnum::CAN_READ_ALL_TASKS,
            PermissionEnum::CAN_ASSIGN_TASKS_TO_USER,
            PermissionEnum::CAN_ASSIGN_TASKS_TO_PROJECT,
            PermissionEnum::CAN_CREATE_PROJECTS,
            PermissionEnum::CAN_EDIT_PROJECTS,
            PermissionEnum::CAN_READ_PROJECTS,
        ],
        'staff' => [
            PermissionEnum::CAN_EDIT_TASKS,
            PermissionEnum::CAN_READ_PROJECTS,
        ],
    ];

    private const ROLE_TARGET_COUNTS = [
        'admin' => 2,
        'teamlead' => 10,
        'staff' => 100,
    ];

    private const FIRST_NAMES = [
        'Max', 'Lisa', 'Anna', 'Leon', 'Emma', 'Paul', 'Mia', 'Felix', 'Sophie', 'Jonas',
        'Laura', 'Lukas', 'Hannah', 'Tim', 'Lena', 'Jan', 'Sarah', 'Finn', 'Marie', 'Nico',
        'Julia', 'Tom', 'Lea', 'Ben', 'Nina', 'Simon', 'Clara', 'David', 'Amelie', 'Moritz',
    ];

    private const LAST_NAMES = [
        'Müller', 'Schmidt', 'Schneider', 'Fischer', 'Weber', 'Meyer', 'Wagner', 'Becker', 'Schulz', 'Hoffmann',
        'Koch', 'Richter', 'Klein', 'Wolf', 'Schröder', 'Neumann', 'Braun', 'Werner', 'Schwarz', 'Hofmann',
        'Zimmermann', 'Schmitt', 'Hartmann', 'Lange', 'Schmitz', 'Krüger', 'Meier', 'Lehmann', 'Köhler', 'Herrmann',
    ];

    private const PROJECT_PREFIXES = [
        'Digitale', 'Neue', 'Innovative', 'Moderne', 'Zentrale', 'Erweiterte', 'Optimierte', 'Integrierte', 'Mobile', 'Cloud',
        'Smart', 'Agile', 'Nachhaltige', 'Zukunfts', 'Kunden', 'Service', 'Mitarbeiter', 'Prozess', 'Daten', 'Qualitäts',
        'Sicherheits', 'Produktions', 'Marketing', 'Vertriebs', 'HR', 'Finance', 'Support', 'Innovations', 'Strategie', 'Entwicklungs',
    ];

    private const PROJECT_SUFFIXES = [
        'Portal', 'Plattform', 'System', 'Hub', 'Center', 'Tool', 'Suite', 'Manager', 'Dashboard', 'Cockpit',
        'Framework', 'Lösung', 'Anwendung', 'Interface', 'Modul', 'Engine', 'Service', 'Gateway', 'Workspace', 'Studio',
        'Zentrum', 'Planer', 'Organizer', 'Assistent', 'Navigator', 'Controller', 'Monitor', 'Analyzer', 'Optimizer', 'Builder',
    ];

    private const TASK_TITLES = [
        'Projektbeschreibung erstellen', 'User Stories ausarbeiten', 'Backlog verfeinern', 'Authentifizierung implementieren',
        'Datenbankschema entwerfen', 'API-Verträge prüfen', 'Integrationstests schreiben', 'UI-Texte optimieren',
        'Abstimmung mit Stakeholdern', 'Deployment-Pipeline planen', 'Monitoring konfigurieren', 'Randfälle dokumentieren',
        'Kundenfeedback analysieren', 'Bugfixes priorisieren', 'Demo-Umgebung einrichten', 'Coding-Richtlinien aktualisieren',
        'Exploratives Testen durchführen', 'Feature-Toggle evaluieren', 'Legacy-Modul refaktorieren', 'Zugriffsrechte prüfen',
        'Onboarding-Checkliste erstellen', 'Sicherheitskonzept überprüfen', 'Datenbankabfragen optimieren', 'Release-Notes koordinieren',
    ];

    private const TASK_NOTES = [
        'Fokus auf schlanke und performante Lösung legen.',
        'Abstimmung mit QA vor Freigabe sicherstellen.',
        'Produktmarketing frühzeitig in den Launch einbinden.',
        'Kompatibilität mit Mobile-Client vor Abschluss prüfen.',
        'Änderungen hinter Feature-Flag absichern für sicheres Rollout.',
        'Schnelles Feedback von mindestens zwei Pilot-Teams einholen.',
        'Annahmen zu Drittanbieter-Integrationen dokumentieren.',
        'Potenzielle Blocker für das nächste Meeting hervorheben.',
        'Pair-Programming nutzen, um Validierung zu beschleunigen.',
        'Erkenntnisse in die gemeinsame Wissensdatenbank eintragen.',
    ];

    private const STATUSES = ['open', 'in_progress', 'review', 'done', 'cancelled'];
    private const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    private ?string $facesDirectory = null;
    private ?string $imagesDirectory = null;
    private ?array $cachedFaceImages = null;
    private ?array $cachedGeneralImages = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ImageStorage $imageStorage,
        private readonly string $projectDir,
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

            $this->loadAvailableImages();

            $this->ensurePermissions();
            $roles = $this->createRoles();
            $usersByRole = $this->createUsers($roles);
            $projectOutcome = $this->createProjectsAndTasks($usersByRole);

            $this->entityManager->flush();

            return [
                'roles' => count($roles),
                'users' => array_reduce($usersByRole, static fn (int $carry, array $users) => $carry + count($users), 0),
                'projects' => count($projectOutcome['projects']),
                'tasks' => $projectOutcome['taskCount'],
                'images' => $projectOutcome['imageCount'],
            ];
        });
    }

    private function purgeDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('TRUNCATE TABLE image, logs, password_reset_tokens, task, project, user_to_role, role_permission, permission, role, "user" RESTART IDENTITY CASCADE');
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
        ];

        foreach ($tables as $label => $query) {
            $count = (int) $connection->fetchOne($query);
            if ($count > 0) {
                throw new \RuntimeException(sprintf('Table %s already contains data. Re-run with --purge to reset before seeding.', $label));
            }
        }
    }

    private function ensurePermissions(): void
    {
        $repo = $this->entityManager->getRepository(Permission::class);
        $existing = [];
        foreach ($repo->findAll() as $p) {
            $existing[$p->getName()] = $p;
        }

        foreach (PermissionEnum::cases() as $enum) {
            if (!isset($existing[$enum->value])) {
                $permission = new Permission();
                $permission->setName($enum->value);
                $permission->setDescription('Auto-generated permission');
                $this->entityManager->persist($permission);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @return array<string, Role>
     */
    private function createRoles(): array
    {
        $permissionRepo = $this->entityManager->getRepository(Permission::class);
        $allPermissions = [];
        foreach ($permissionRepo->findAll() as $p) {
            $allPermissions[$p->getName()] = $p;
        }

        $catalog = [];
        foreach (self::ROLE_PROFILES as $slug => $permissions) {
            $role = new Role();
            $role->setName($slug);
            
            foreach ($permissions as $enum) {
                if (isset($allPermissions[$enum->value])) {
                    $role->addPermission($allPermissions[$enum->value]);
                }
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

                if ($i === 0) {
                    $fixedUsers = [
                        'admin' => ['name' => 'Admin Test', 'email' => 'admin@changeit.de'],
                        'teamlead' => ['name' => 'Teamlead Test', 'email' => 'teamlead@changeit.de'],
                        'staff' => ['name' => 'Staff Test', 'email' => 'staff@changeit.de'],
                    ];
                    $user->setName($fixedUsers[$roleSlug]['name']);
                    $user->setEmail($fixedUsers[$roleSlug]['email']);
                    
                    $password = $this->passwordHasher->hashPassword($user, '123');
                } else {
                    $nameToken = $this->generateNameToken($roleSlug, $i);
                    $user->setName($nameToken['display']);
                    $user->setEmail($nameToken['email']);
                    
                    $password = $this->passwordHasher->hashPassword($user, 'Password123!');
                }

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

                $shouldHaveProfileImage = ($i === 0 && $roleSlug !== 'admin') || random_int(0, 100) < 80;
                if ($shouldHaveProfileImage) {
                    $randomFace = $this->getRandomFaceImage();
                    if ($randomFace !== null) {
                        $profileImage = $this->createImageFromFile($randomFace, $user, 'profile');
                        if ($profileImage !== null) {
                            $user->setProfileImage($profileImage);
                        }
                    }
                }

                $this->entityManager->persist($user);
                $usersByRole[$roleSlug][] = $user;
            }
        }

        return $usersByRole;
    }

    /**
     * @param array<string, User[]> $usersByRole
     * @return array{projects: Project[], taskCount: int, imageCount: int}
     */
    private function createProjectsAndTasks(array $usersByRole): array
    {
        $projects = [];
        $taskCount = 0;
        $imageCount = 0;

        $projectTaskAllocations = $this->buildTaskAllocation(count: 100);

        foreach ($projectTaskAllocations as $index => $taskTotal) {
            $project = new Project();
            $project->setName($this->generateProjectName($index));
            $project->setDescription($this->randomProjectDescription());

            $creatorPool = array_merge($usersByRole['admin'], $usersByRole['teamlead']);
            $creator = $this->pickRandom($creatorPool);
            $project->setCreatedByUser($creator);

            $createdAt = $this->randomPastDate(240);
            $project->setCreatedAt($createdAt);

            $this->entityManager->persist($project);
            $projects[] = $project;

            if (random_int(0, 100) < 60) {
                $imageCount += $this->createImagesForProject($project, $creator);
            }

            $taskResult = $this->createTasksForProject($project, $taskTotal, $usersByRole['teamlead'], $usersByRole['staff'], $createdAt);
            $taskCount += $taskResult['taskCount'];
            $imageCount += $taskResult['imageCount'];
        }

        return ['projects' => $projects, 'taskCount' => $taskCount, 'imageCount' => $imageCount];
    }

    /**
     * @param User[] $creators
     * @param User[] $assignees
     * @return array{taskCount: int, imageCount: int}
     */
    private function createTasksForProject(Project $project, int $taskTotal, array $creators, array $assignees, \DateTimeImmutable $projectCreatedAt): array
    {
        if ($taskTotal === 0) {
            return ['taskCount' => 0, 'imageCount' => 0];
        }

        $created = 0;
        $imageCount = 0;

        for ($i = 0; $i < $taskTotal; $i++) {
            $task = new Task();
            $task->setProject($project);
            $task->setTitle($this->generateTaskTitle($project->getName(), $i));
            $task->setDescription($this->maybePick(self::TASK_NOTES));
            $task->setStatus($this->pickRandom(self::STATUSES));
            $task->setPriority($this->pickRandom(self::PRIORITIES));

            $createdAt = $this->randomTaskCreationDate($projectCreatedAt);
            $task->setCreatedAt($createdAt);
            $creator = $this->pickRandom($creators);
            $task->setCreatedByUser($creator);

            if (random_int(0, 100) < 85) {
                $assigneeCount = random_int(1, min(3, count($assignees)));
                $selectedAssignees = (array) array_rand(array_flip(range(0, count($assignees) - 1)), $assigneeCount);
                foreach ($selectedAssignees as $index) {
                    $task->assignUser($assignees[$index]);
                }
            }

            if (random_int(0, 100) < 70) {
                $task->setDueDate($this->randomDueDate($createdAt));
            }

            if (random_int(0, 100) < 60) {
                $task->setUpdatedAt($this->randomUpdateDate($createdAt));
            }

            $this->entityManager->persist($task);
            $created++;

            if (random_int(0, 100) < 30) {
                $imageCount += $this->createImagesForTask($task, $creator);
            }
        }

        return ['taskCount' => $created, 'imageCount' => $imageCount];
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
        $email = sprintf('%s@changeit.de', $emailLocal);

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
            'Fokussiert auf die Verbesserung der operativen Effizienz über alle Teams hinweg.',
            'Zielt auf eine bessere Onboarding-Erfahrung für neue Kunden ab.',
            'Konsolidiert Legacy-Tools in einen einheitlichen Workflow.',
            'Erkundet Automatisierungsmöglichkeiten zur Reduzierung manueller Aufwände.',
            'Schafft die Grundlage für kommende Mobile-App-Entwicklungen.',
            'Schafft Alignment zwischen Design, Produkt und Engineering.',
            'Liefert Insights-Dashboards wie von der Geschäftsleitung gefordert.',
            'Evaluiert eine neue Integration für Enterprise-Kunden.',
            'Härtet zentrale Authentifizierungspfade für externe Audits.',
            'Führt optimierte Kollaborationsrituale für Teams ein.',
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

    private function loadAvailableImages(): void
    {
        $facesDir = $this->projectDir.'/dev-assets/random_faces';
        $imagesDir = $this->projectDir.'/dev-assets/random_images';

        if (is_dir($facesDir)) {
            $this->facesDirectory = $facesDir;
        }

        if (is_dir($imagesDir)) {
            $this->imagesDirectory = $imagesDir;
        }
    }

    private function getRandomFaceImage(): ?string
    {
        if ($this->facesDirectory === null) {
            return null;
        }

        if ($this->cachedFaceImages === null) {
            $this->cachedFaceImages = $this->scanImagesRecursive($this->facesDirectory);
        }

        if (count($this->cachedFaceImages) === 0) {
            return null;
        }

        return $this->pickRandom($this->cachedFaceImages);
    }

    private function getRandomGeneralImage(): ?string
    {
        if ($this->imagesDirectory === null) {
            return null;
        }

        if ($this->cachedGeneralImages === null) {
            $this->cachedGeneralImages = $this->scanImagesRecursive($this->imagesDirectory);
        }

        if (count($this->cachedGeneralImages) === 0) {
            return null;
        }

        return $this->pickRandom($this->cachedGeneralImages);
    }

    private function scanImagesRecursive(string $directory): array
    {
        $images = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                    $images[] = $file->getPathname();
                }
            }
        }

        return $images;
    }

    private function createImageFromFile(string $filePath, User $uploader, string $type): ?Image
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $image = new Image();
        $image->setType($type);
        $image->setUploadedByUser($uploader);
        $image->setUploadedAt($this->randomPastDate(180));
        $image->setFileSize((int) filesize($filePath));

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $image->setFileType($extension);

        $this->entityManager->persist($image);
        $this->imageStorage->store($image, $filePath);

        return $image;
    }

    private function createImagesForProject(Project $project, User $uploader): int
    {
        $imageCount = random_int(1, 4);
        $created = 0;

        for ($i = 0; $i < $imageCount; $i++) {
            $randomImage = $this->getRandomGeneralImage();
            if ($randomImage === null) {
                break;
            }
            $image = $this->createImageFromFile($randomImage, $uploader, 'project_attachment');
            if ($image !== null) {
                $image->setProject($project);
                $created++;
            }
        }

        return $created;
    }

    private function createImagesForTask(Task $task, User $uploader): int
    {
        $imageCount = random_int(1, 3);
        $created = 0;

        for ($i = 0; $i < $imageCount; $i++) {
            $randomImage = $this->getRandomGeneralImage();
            if ($randomImage === null) {
                break;
            }
            $image = $this->createImageFromFile($randomImage, $uploader, 'task_attachment');
            if ($image !== null) {
                $image->setTask($task);
                $created++;
            }
        }

        return $created;
    }
}
