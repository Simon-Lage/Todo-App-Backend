<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204144702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE image DROP CONSTRAINT IF EXISTS fk_c53d045fa76ed395');
        $this->addSql('DROP INDEX IF EXISTS idx_c53d045fa76ed395');
        $this->addSql('ALTER TABLE image DROP COLUMN IF EXISTS user_id');
        $this->addSql('ALTER INDEX IF EXISTS idx_8d0d87798db60186 RENAME TO IDX_6DEED38D8DB60186');
        $this->addSql('ALTER INDEX IF EXISTS idx_8d0d8779a76ed395 RENAME TO IDX_6DEED38DA76ED395');
        $this->addSql('ALTER TABLE "user" ADD profile_image_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "user".profile_image_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649C4CF44DC FOREIGN KEY (profile_image_id) REFERENCES image (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8D93D649C4CF44DC ON "user" (profile_image_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER INDEX idx_6deed38d8db60186 RENAME TO idx_8d0d87798db60186');
        $this->addSql('ALTER INDEX idx_6deed38da76ed395 RENAME TO idx_8d0d8779a76ed395');
        $this->addSql('ALTER TABLE image ADD user_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN image.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT fk_c53d045fa76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_c53d045fa76ed395 ON image (user_id)');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649C4CF44DC');
        $this->addSql('DROP INDEX IDX_8D93D649C4CF44DC');
        $this->addSql('ALTER TABLE "user" DROP profile_image_id');
    }
}
