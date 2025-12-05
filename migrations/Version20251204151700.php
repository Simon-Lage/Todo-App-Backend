<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204151700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_3967a216a76ed395');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_3967A216A76ED395 ON password_reset_tokens (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_3967A216A76ED395');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_3967a216a76ed395 ON password_reset_tokens (user_id)');
    }
}
