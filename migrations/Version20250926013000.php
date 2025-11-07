<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250926013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create app_config table to store allowed email domains for registration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_config (id UUID NOT NULL, allowed_email_domains JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN app_config.id IS \'(DC2Type:uuid)\'');
        $this->addSql('INSERT INTO app_config (id, allowed_email_domains) VALUES (\'00000000-0000-0000-0000-000000000001\', \'["changeit.test"]\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_config');
    }
}
