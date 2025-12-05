<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204124629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE permission (id UUID NOT NULL, name VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E04992AA5E237E06 ON permission (name)');
        $this->addSql('COMMENT ON COLUMN permission.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE role_permission (role_id UUID NOT NULL, permission_id UUID NOT NULL, PRIMARY KEY(role_id, permission_id))');
        $this->addSql('CREATE INDEX IDX_6F7DF886D60322AC ON role_permission (role_id)');
        $this->addSql('CREATE INDEX IDX_6F7DF886FED90CCA ON role_permission (permission_id)');
        $this->addSql('COMMENT ON COLUMN role_permission.role_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN role_permission.permission_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886FED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role DROP perm_can_create_user');
        $this->addSql('ALTER TABLE role DROP perm_can_edit_user');
        $this->addSql('ALTER TABLE role DROP perm_can_read_user');
        $this->addSql('ALTER TABLE role DROP perm_can_delete_user');
        $this->addSql('ALTER TABLE role DROP perm_can_create_tasks');
        $this->addSql('ALTER TABLE role DROP perm_can_edit_tasks');
        $this->addSql('ALTER TABLE role DROP perm_can_read_all_tasks');
        $this->addSql('ALTER TABLE role DROP perm_can_delete_tasks');
        $this->addSql('ALTER TABLE role DROP perm_can_assign_tasks_to_user');
        $this->addSql('ALTER TABLE role DROP perm_can_assign_tasks_to_project');
        $this->addSql('ALTER TABLE role DROP perm_can_create_projects');
        $this->addSql('ALTER TABLE role DROP perm_can_edit_projects');
        $this->addSql('ALTER TABLE role DROP perm_can_read_projects');
        $this->addSql('ALTER TABLE role DROP perm_can_delete_projects');
        $this->addSql('ALTER TABLE role DROP perm_can_create_roles');
        $this->addSql('ALTER TABLE role DROP perm_can_edit_roles');
        $this->addSql('ALTER TABLE role DROP perm_can_read_roles');
        $this->addSql('ALTER TABLE role DROP perm_can_delete_roles');
        $this->addSql('ALTER INDEX uniq_role_name RENAME TO UNIQ_57698A6A5E237E06');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE role_permission DROP CONSTRAINT FK_6F7DF886D60322AC');
        $this->addSql('ALTER TABLE role_permission DROP CONSTRAINT FK_6F7DF886FED90CCA');
        $this->addSql('DROP TABLE permission');
        $this->addSql('DROP TABLE role_permission');
        $this->addSql('ALTER TABLE role ADD perm_can_create_user BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_edit_user BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_read_user BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_delete_user BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_create_tasks BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_edit_tasks BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_read_all_tasks BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_delete_tasks BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_assign_tasks_to_user BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_assign_tasks_to_project BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_create_projects BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_edit_projects BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_read_projects BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_delete_projects BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_create_roles BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_edit_roles BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_read_roles BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE role ADD perm_can_delete_roles BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER INDEX uniq_57698a6a5e237e06 RENAME TO uniq_role_name');
    }
}
