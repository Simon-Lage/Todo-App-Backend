<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251204170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unused description column from permission';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE permission DROP COLUMN description');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE permission ADD description TEXT DEFAULT NULL');
    }
}
