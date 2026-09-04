<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805142722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace variant is_online with a safe publication workflow status and dates.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clothes DROP is_online');
        $this->addSql("ALTER TABLE clothes_variant ADD publication_status VARCHAR(20) DEFAULT 'draft' NOT NULL, ADD scheduled_publication_at DATETIME DEFAULT NULL, ADD published_at DATETIME DEFAULT NULL, ADD archived_at DATETIME DEFAULT NULL");
        $this->addSql("UPDATE clothes_variant SET publication_status = CASE WHEN is_online = 1 THEN 'online' ELSE 'draft' END");
        $this->addSql('ALTER TABLE clothes_variant DROP is_online');
        $this->addSql('CREATE INDEX IDX_CLOTHES_VARIANT_PUBLICATION_STATUS ON clothes_variant (publication_status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clothes ADD is_online TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('DROP INDEX IDX_CLOTHES_VARIANT_PUBLICATION_STATUS ON clothes_variant');
        $this->addSql('ALTER TABLE clothes_variant ADD is_online TINYINT DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE clothes_variant SET is_online = CASE WHEN publication_status = 'online' THEN 1 ELSE 0 END");
        $this->addSql('ALTER TABLE clothes_variant DROP publication_status, DROP scheduled_publication_at, DROP published_at, DROP archived_at');
    }
}
