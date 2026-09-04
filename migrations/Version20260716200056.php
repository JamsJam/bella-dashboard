<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260716200056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les accessoires de visage et adapte les noms des visages existants avec le suffixe -none-.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE face_accessory (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_729D6765E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE faces ADD accessory_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE faces ADD CONSTRAINT FK_C8DD2F8B27E8CC78 FOREIGN KEY (accessory_id) REFERENCES face_accessory (id)');
        $this->addSql('CREATE INDEX IDX_C8DD2F8B27E8CC78 ON faces (accessory_id)');
        $this->addSql("UPDATE faces SET name = CONCAT(CASE WHEN name LIKE 'face\\_\\_%' THEN CONCAT('visage__', SUBSTRING(name, 7)) ELSE name END, '__-none-') WHERE accessory_id IS NULL AND name NOT LIKE '%\\_\\_-none-'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE faces SET name = CASE WHEN name LIKE 'visage\\_\\_%\\_\\_-none-' THEN CONCAT('face__', SUBSTRING(name, 9, CHAR_LENGTH(name) - 17)) ELSE LEFT(name, CHAR_LENGTH(name) - 9) END WHERE accessory_id IS NULL AND name LIKE '%\\_\\_-none-'");
        $this->addSql('ALTER TABLE faces DROP FOREIGN KEY FK_C8DD2F8B27E8CC78');
        $this->addSql('DROP INDEX IDX_C8DD2F8B27E8CC78 ON faces');
        $this->addSql('ALTER TABLE faces DROP accessory_id');
        $this->addSql('DROP TABLE face_accessory');
    }
}
