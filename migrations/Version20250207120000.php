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
        $this->addSql('ALTER TABLE image ADD COLUMN IF NOT EXISTS user_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN image.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_c53d045fa76ed395 ON image (user_id)');
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'fk_c53d045fa76ed395'
          AND table_name = 'image'
    ) THEN
        ALTER TABLE image ADD CONSTRAINT fk_c53d045fa76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
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
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'fk_c53d045fa76ed395'
          AND table_name = 'image'
    ) THEN
        ALTER TABLE image DROP CONSTRAINT fk_c53d045fa76ed395;
    END IF;
END $$;
SQL);
        $this->addSql('DROP INDEX IF EXISTS idx_c53d045fa76ed395');
        $this->addSql('ALTER TABLE image DROP COLUMN IF EXISTS user_id');
    }
}
