<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250207120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure image.user_id column exists with FK to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
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
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_name = 'image'
    ) THEN
        IF EXISTS (
            SELECT 1 FROM information_schema.table_constraints
            WHERE constraint_name = 'fk_c53d045fa76ed395'
              AND table_name = 'image'
        ) THEN
            ALTER TABLE image DROP CONSTRAINT fk_c53d045fa76ed395;
        END IF;
        
        IF EXISTS (
            SELECT 1 FROM pg_indexes
            WHERE indexname = 'idx_c53d045fa76ed395'
        ) THEN
            DROP INDEX idx_c53d045fa76ed395;
        END IF;
        
        IF EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_name = 'image' AND column_name = 'user_id'
        ) THEN
            ALTER TABLE image DROP COLUMN user_id;
        END IF;
    END IF;
END $$;
SQL);
    }
}
