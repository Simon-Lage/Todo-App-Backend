<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop app_config table - configuration moved to config/packages/app.yaml
 */
final class Version20251204150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove app_config table and move configuration to YAML files';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_config');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_config (id UUID NOT NULL, allowed_email_domains JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN app_config.id IS \'(DC2Type:uuid)\'');
    }
}

