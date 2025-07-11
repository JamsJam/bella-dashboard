<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250707231424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE morphotype ADD morphologie_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE morphotype ADD CONSTRAINT FK_140B0A9B5A377682 FOREIGN KEY (morphologie_id) REFERENCES morphologie (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_140B0A9B5A377682 ON morphotype (morphologie_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE morphotype DROP FOREIGN KEY FK_140B0A9B5A377682
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_140B0A9B5A377682 ON morphotype
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE morphotype DROP morphologie_id
        SQL);
    }
}
