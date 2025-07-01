<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250616203247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE project_technology (project_id INT NOT NULL, technology_id INT NOT NULL, INDEX IDX_ECC5297F166D1F9C (project_id), INDEX IDX_ECC5297F4235D463 (technology_id), PRIMARY KEY(project_id, technology_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology ADD CONSTRAINT FK_ECC5297F166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology ADD CONSTRAINT FK_ECC5297F4235D463 FOREIGN KEY (technology_id) REFERENCES technology (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology DROP FOREIGN KEY FK_ECC5297F166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology DROP FOREIGN KEY FK_ECC5297F4235D463
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE project_technology
        SQL);
    }
}
