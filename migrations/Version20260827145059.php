<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260827145059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow free-text values, including minimum and maximum ranges, in size guides.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE size_guide_measurement CHANGE value value VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE size_guide_measurement CHANGE value value NUMERIC(6, 2) NOT NULL');
    }
}
