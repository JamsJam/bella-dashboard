<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260617153615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE body_clothes (body_id INT NOT NULL, clothes_id INT NOT NULL, INDEX IDX_D98F0D1A9B621D84 (body_id), INDEX IDX_D98F0D1A271E85C0 (clothes_id), PRIMARY KEY (body_id, clothes_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE body_clothes ADD CONSTRAINT FK_D98F0D1A9B621D84 FOREIGN KEY (body_id) REFERENCES body (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE body_clothes ADD CONSTRAINT FK_D98F0D1A271E85C0 FOREIGN KEY (clothes_id) REFERENCES clothes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE body DROP FOREIGN KEY `FK_DBA80BB2D554487F`');
        $this->addSql('DROP INDEX IDX_DBA80BB2D554487F ON body');
        $this->addSql('ALTER TABLE body DROP clothe_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE body_clothes DROP FOREIGN KEY FK_D98F0D1A9B621D84');
        $this->addSql('ALTER TABLE body_clothes DROP FOREIGN KEY FK_D98F0D1A271E85C0');
        $this->addSql('DROP TABLE body_clothes');
        $this->addSql('ALTER TABLE body ADD clothe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE body ADD CONSTRAINT `FK_DBA80BB2D554487F` FOREIGN KEY (clothe_id) REFERENCES clothes (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_DBA80BB2D554487F ON body (clothe_id)');
    }
}
