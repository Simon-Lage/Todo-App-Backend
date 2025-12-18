<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix:image-user-id-column',
    description: 'Fix missing user_id column in image table',
)]
final class FixImageUserIdColumnCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->connection->executeStatement(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_name = 'image'
    ) THEN
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_name = 'image' AND column_name = 'user_id'
        ) THEN
            ALTER TABLE image ADD COLUMN user_id UUID DEFAULT NULL;
            COMMENT ON COLUMN image.user_id IS '(DC2Type:uuid)';
        END IF;
        
        IF NOT EXISTS (
            SELECT 1 FROM pg_indexes
            WHERE indexname = 'idx_c53d045fa76ed395'
        ) THEN
            CREATE INDEX idx_c53d045fa76ed395 ON image (user_id);
        END IF;
        
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.table_constraints
            WHERE constraint_name = 'fk_c53d045fa76ed395'
              AND table_name = 'image'
        ) THEN
            ALTER TABLE image ADD CONSTRAINT fk_c53d045fa76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
        END IF;
    END IF;
END $$;
SQL);

            $io->success('Successfully fixed image.user_id column');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Failed to fix image.user_id column: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

