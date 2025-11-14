<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925160833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE image (id UUID NOT NULL, uploaded_by_user_id UUID NOT NULL, project_id UUID DEFAULT NULL, task_id UUID DEFAULT NULL, user_id UUID DEFAULT NULL, file_type VARCHAR(10) NOT NULL, file_size INT NOT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C53D045F861E61EA ON image (uploaded_by_user_id)');
        $this->addSql('CREATE INDEX IDX_C53D045F166D1F9C ON image (project_id)');
        $this->addSql('CREATE INDEX IDX_C53D045F8DB60186 ON image (task_id)');
        $this->addSql('CREATE INDEX IDX_C53D045FA76ED395 ON image (user_id)');
        $this->addSql('COMMENT ON COLUMN image.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN image.uploaded_by_user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN image.project_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN image.task_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN image.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN image.uploaded_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE logs (id UUID NOT NULL, performed_by_user_id UUID NOT NULL, action VARCHAR(255) NOT NULL, performed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, details TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F08FC65C43F2ED96 ON logs (performed_by_user_id)');
        $this->addSql('COMMENT ON COLUMN logs.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN logs.performed_by_user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN logs.performed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project (id UUID NOT NULL, created_by_user_id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EE5E237E06 ON project (name)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE7D182D95 ON project (created_by_user_id)');
        $this->addSql('COMMENT ON COLUMN project.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN project.created_by_user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN project.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE role (id UUID NOT NULL, perm_can_create_user BOOLEAN NOT NULL, perm_can_edit_user BOOLEAN NOT NULL, perm_can_read_user BOOLEAN NOT NULL, perm_can_delete_user BOOLEAN NOT NULL, perm_can_create_tasks BOOLEAN NOT NULL, perm_can_edit_tasks BOOLEAN NOT NULL, perm_can_read_all_tasks BOOLEAN NOT NULL, perm_can_delete_tasks BOOLEAN NOT NULL, perm_can_assign_tasks_to_user BOOLEAN NOT NULL, perm_can_assign_tasks_to_project BOOLEAN NOT NULL, perm_can_create_projects BOOLEAN NOT NULL, perm_can_edit_projects BOOLEAN NOT NULL, perm_can_read_projects BOOLEAN NOT NULL, perm_can_delete_projects BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN role.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE task (id UUID NOT NULL, created_by_user_id UUID NOT NULL, assigned_to_user_id UUID DEFAULT NULL, project_id UUID DEFAULT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(50) NOT NULL, priority VARCHAR(50) NOT NULL, due_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_527EDB257D182D95 ON task (created_by_user_id)');
        $this->addSql('CREATE INDEX IDX_527EDB2511578D11 ON task (assigned_to_user_id)');
        $this->addSql('CREATE INDEX IDX_527EDB25166D1F9C ON task (project_id)');
        $this->addSql('COMMENT ON COLUMN task.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN task.created_by_user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN task.assigned_to_user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN task.project_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN task.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE user_to_role (user_id UUID NOT NULL, role_id UUID NOT NULL, PRIMARY KEY(user_id, role_id))');
        $this->addSql('CREATE INDEX IDX_E88A85AFA76ED395 ON user_to_role (user_id)');
        $this->addSql('CREATE INDEX IDX_E88A85AFD60322AC ON user_to_role (role_id)');
        $this->addSql('COMMENT ON COLUMN user_to_role.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_to_role.role_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F861E61EA FOREIGN KEY (uploaded_by_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045FA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE logs ADD CONSTRAINT FK_F08FC65C43F2ED96 FOREIGN KEY (performed_by_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE7D182D95 FOREIGN KEY (created_by_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB257D182D95 FOREIGN KEY (created_by_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2511578D11 FOREIGN KEY (assigned_to_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_to_role ADD CONSTRAINT FK_E88A85AFA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_to_role ADD CONSTRAINT FK_E88A85AFD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD is_password_temporary BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN create_at TO temporary_password_created_at');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6495E237E06 ON "user" (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE image DROP CONSTRAINT FK_C53D045F861E61EA');
        $this->addSql('ALTER TABLE image DROP CONSTRAINT FK_C53D045F166D1F9C');
        $this->addSql('ALTER TABLE image DROP CONSTRAINT FK_C53D045F8DB60186');
        $this->addSql('ALTER TABLE image DROP CONSTRAINT FK_C53D045FA76ED395');
        $this->addSql('ALTER TABLE logs DROP CONSTRAINT FK_F08FC65C43F2ED96');
        $this->addSql('ALTER TABLE project DROP CONSTRAINT FK_2FB3D0EE7D182D95');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB257D182D95');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB2511578D11');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB25166D1F9C');
        $this->addSql('ALTER TABLE user_to_role DROP CONSTRAINT FK_E88A85AFA76ED395');
        $this->addSql('ALTER TABLE user_to_role DROP CONSTRAINT FK_E88A85AFD60322AC');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE logs');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE user_to_role');
        $this->addSql('DROP INDEX UNIQ_8D93D6495E237E06');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74');
        $this->addSql('ALTER TABLE "user" ADD create_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE "user" DROP is_password_temporary');
        $this->addSql('ALTER TABLE "user" DROP temporary_password_created_at');
        $this->addSql('ALTER TABLE "user" DROP created_at');
        $this->addSql('COMMENT ON COLUMN "user".create_at IS \'(DC2Type:datetime_immutable)\'');
    }
}
