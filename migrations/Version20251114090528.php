<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114090528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE role ADD name VARCHAR(100) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_create_roles BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_edit_roles BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_read_roles BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_delete_roles BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('UPDATE role SET name = CONCAT(\'role-\', SUBSTRING(id::text, 1, 8)) WHERE name = \'\'');
        $this->addSql('UPDATE role SET perm_can_create_roles = FALSE WHERE perm_can_create_roles IS NULL');
        $this->addSql('UPDATE role SET perm_can_edit_roles = FALSE WHERE perm_can_edit_roles IS NULL');
        $this->addSql('UPDATE role SET perm_can_read_roles = FALSE WHERE perm_can_read_roles IS NULL');
        $this->addSql('UPDATE role SET perm_can_delete_roles = FALSE WHERE perm_can_delete_roles IS NULL');
        $this->addSql('ALTER TABLE role ALTER COLUMN name DROP DEFAULT');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT UNIQ_role_name UNIQUE (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE role DROP CONSTRAINT UNIQ_role_name');
        $this->addSql('ALTER TABLE role DROP name');
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE role DROP perm_can_create_roles');
        $this->addSql('ALTER TABLE role DROP perm_can_edit_roles');
        $this->addSql('ALTER TABLE role DROP perm_can_read_roles');
        $this->addSql('ALTER TABLE role DROP perm_can_delete_roles');
    }
}
