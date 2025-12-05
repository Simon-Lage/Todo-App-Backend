<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Change task assignment from single user to multiple users (M:N relationship)
 */
final class Version20251204160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change task assignment from 1:N to M:N relationship (task_assignees table)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_assignees (task_id UUID NOT NULL, user_id UUID NOT NULL, PRIMARY KEY(task_id, user_id))');
        $this->addSql('CREATE INDEX IDX_8D0D87798DB60186 ON task_assignees (task_id)');
        $this->addSql('CREATE INDEX IDX_8D0D8779A76ED395 ON task_assignees (user_id)');
        $this->addSql('COMMENT ON COLUMN task_assignees.task_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN task_assignees.user_id IS \'(DC2Type:uuid)\'');
        
        $this->addSql('ALTER TABLE task_assignees ADD CONSTRAINT FK_8D0D87798DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task_assignees ADD CONSTRAINT FK_8D0D8779A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        
        $this->addSql('INSERT INTO task_assignees (task_id, user_id) SELECT id, assigned_to_user_id FROM task WHERE assigned_to_user_id IS NOT NULL');
        
        $this->addSql('ALTER TABLE task DROP CONSTRAINT IF EXISTS fk_527edb2511578d11');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT IF EXISTS fk_527edb25e65afb48');
        $this->addSql('DROP INDEX IF EXISTS idx_527edb2511578d11');
        $this->addSql('DROP INDEX IF EXISTS idx_527edb25e65afb48');
        $this->addSql('ALTER TABLE task DROP COLUMN IF EXISTS assigned_to_user_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task ADD assigned_to_user_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN task.assigned_to_user_id IS \'(DC2Type:uuid)\'');
        
        $this->addSql('UPDATE task SET assigned_to_user_id = (SELECT user_id FROM task_assignees WHERE task_id = task.id LIMIT 1)');
        
        $this->addSql('ALTER TABLE task_assignees DROP CONSTRAINT FK_8D0D87798DB60186');
        $this->addSql('ALTER TABLE task_assignees DROP CONSTRAINT FK_8D0D8779A76ED395');
        $this->addSql('DROP TABLE task_assignees');
        
        $this->addSql('ALTER TABLE task ADD CONSTRAINT fk_527edb25e65afb48 FOREIGN KEY (assigned_to_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_527edb25e65afb48 ON task (assigned_to_user_id)');
    }
}

