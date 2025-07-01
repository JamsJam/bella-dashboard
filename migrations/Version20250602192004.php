<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250602192004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, technologies JSON DEFAULT NULL, images JSON DEFAULT NULL, projectlink VARCHAR(255) DEFAULT NULL, is_online TINYINT(1) NOT NULL, is_gitpublic TINYINT(1) NOT NULL, gitlink VARCHAR(255) DEFAULT NULL, casestudy LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag_project (tag_id INT NOT NULL, project_id INT NOT NULL, INDEX IDX_1D82FD44BAD26311 (tag_id), INDEX IDX_1D82FD44166D1F9C (project_id), PRIMARY KEY(tag_id, project_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_project ADD CONSTRAINT FK_1D82FD44BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_project ADD CONSTRAINT FK_1D82FD44166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_project DROP FOREIGN KEY FK_1D82FD44BAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_project DROP FOREIGN KEY FK_1D82FD44166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE project
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag_project
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
