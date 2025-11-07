<?php

declare(strict_types=1);

namespace App\Command;

use App\Dev\DataGenerator\RandomDataSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:dev:seed-random-data',
    description: 'Populate the local database with random development data.',
)]
final class GenerateRandomDataCommand extends Command
{
    public function __construct(private readonly RandomDataSeeder $seeder)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('purge', null, InputOption::VALUE_NONE, 'Purge existing data before generating new fixtures.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $purge = (bool) $input->getOption('purge');

        try {
            $result = $this->seeder->seed($purge);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success('Random development data generated successfully.');
        $io->table([
            'Roles',
            'Users',
            'Projects',
            'Tasks',
        ], [[
            $result['roles'],
            $result['users'],
            $result['projects'],
            $result['tasks'],
        ]]);

        if (!$purge) {
            $io->note('Pass --purge to reset existing data before seeding.');
        }

        return Command::SUCCESS;
    }
}
