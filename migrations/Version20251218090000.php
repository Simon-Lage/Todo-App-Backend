<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251218090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add project team leads, project completion and task finalization';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD is_completed BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('ALTER TABLE project ADD completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD completed_by_user_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE983A77F1 FOREIGN KEY (completed_by_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE983A77F1 ON project (completed_by_user_id)');

        $this->addSql('CREATE TABLE project_team_leads (project_id UUID NOT NULL, user_id UUID NOT NULL, PRIMARY KEY(project_id, user_id))');
        $this->addSql('CREATE INDEX IDX_8B5F8FA8166D1F9C ON project_team_leads (project_id)');
        $this->addSql('CREATE INDEX IDX_8B5F8FA8A76ED395 ON project_team_leads (user_id)');
        $this->addSql('ALTER TABLE project_team_leads ADD CONSTRAINT FK_8B5F8FA8166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_team_leads ADD CONSTRAINT FK_8B5F8FA8A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE task ADD finalized_by_user_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE task ADD finalized_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25B7E9E1A3 FOREIGN KEY (finalized_by_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_527EDB25B7E9E1A3 ON task (finalized_by_user_id)');

        $this->addSql('ALTER TABLE task ADD reviewer_user_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25B5C8F4A9 FOREIGN KEY (reviewer_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_527EDB25B5C8F4A9 ON task (reviewer_user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB25B5C8F4A9');
        $this->addSql('DROP INDEX IDX_527EDB25B5C8F4A9');
        $this->addSql('ALTER TABLE task DROP reviewer_user_id');

        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB25B7E9E1A3');
        $this->addSql('DROP INDEX IDX_527EDB25B7E9E1A3');
        $this->addSql('ALTER TABLE task DROP finalized_by_user_id');
        $this->addSql('ALTER TABLE task DROP finalized_at');

        $this->addSql('DROP TABLE project_team_leads');

        $this->addSql('ALTER TABLE project DROP CONSTRAINT FK_2FB3D0EE983A77F1');
        $this->addSql('DROP INDEX IDX_2FB3D0EE983A77F1');
        $this->addSql('ALTER TABLE project DROP is_completed');
        $this->addSql('ALTER TABLE project DROP completed_at');
        $this->addSql('ALTER TABLE project DROP completed_by_user_id');
    }
}
