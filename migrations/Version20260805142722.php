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
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clothes DROP is_online');
        $this->addSql('ALTER TABLE clothes_variant ADD publication_status VARCHAR(255) NOT NULL, ADD scheduled_publication_at DATETIME DEFAULT NULL, ADD published_at DATETIME DEFAULT NULL, ADD archived_at DATETIME DEFAULT NULL, DROP is_online');
        $this->addSql('CREATE INDEX IDX_CLOTHES_VARIANT_PUBLICATION_STATUS ON clothes_variant (publication_status)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clothes ADD is_online TINYINT NOT NULL');
        $this->addSql('DROP INDEX IDX_CLOTHES_VARIANT_PUBLICATION_STATUS ON clothes_variant');
        $this->addSql('ALTER TABLE clothes_variant ADD is_online TINYINT NOT NULL, DROP publication_status, DROP scheduled_publication_at, DROP published_at, DROP archived_at');
    }
}
