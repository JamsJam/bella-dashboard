<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250731214238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE clothes ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', ADD edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clothescolor CHANGE hexa hexa VARCHAR(6) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE clothes DROP created_at, DROP edited_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clothescolor CHANGE hexa hexa VARCHAR(6) NOT NULL
        SQL);
    }
}
