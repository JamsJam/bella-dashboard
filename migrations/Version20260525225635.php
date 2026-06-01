<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525225635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clothes RENAME INDEX idx_clothes_size_guide TO IDX_3079B48C89D68997');
        $this->addSql('ALTER TABLE measurement_type CHANGE code code VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE size_guide ADD unit VARCHAR(8) NOT NULL, DROP default_unit');
        $this->addSql('ALTER TABLE size_guide_measurement DROP FOREIGN KEY `FK_SIZE_GUIDE_MEASUREMENT_SIZE`');
        $this->addSql('DROP INDEX IDX_SIZE_GUIDE_MEASUREMENT_SIZE ON size_guide_measurement');
        $this->addSql('DROP INDEX UNIQ_SIZE_GUIDE_MEASUREMENT_TYPE ON size_guide_measurement');
        $this->addSql('ALTER TABLE size_guide_measurement CHANGE value value NUMERIC(6, 2) NOT NULL, CHANGE unit unit VARCHAR(8) NOT NULL, CHANGE size_id size_guide_size_id INT NOT NULL');
        $this->addSql('ALTER TABLE size_guide_measurement ADD CONSTRAINT FK_41D7B38DFBAAC0F FOREIGN KEY (size_guide_size_id) REFERENCES size_guide_size (id)');
        $this->addSql('CREATE INDEX IDX_41D7B38DFBAAC0F ON size_guide_measurement (size_guide_size_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SIZE_GUIDE_MEASUREMENT_TYPE ON size_guide_measurement (size_guide_size_id, type_id)');
        $this->addSql('ALTER TABLE size_guide_measurement RENAME INDEX idx_size_guide_measurement_type TO IDX_41D7B38DC54C8C93');
        $this->addSql('ALTER TABLE size_guide_size DROP FOREIGN KEY `FK_SIZE_GUIDE_SIZE_GUIDE`');
        $this->addSql('ALTER TABLE size_guide_size CHANGE label label VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE size_guide_size ADD CONSTRAINT FK_34C99C7389D68997 FOREIGN KEY (size_guide_id) REFERENCES size_guide (id)');
        $this->addSql('ALTER TABLE size_guide_size RENAME INDEX idx_size_guide_size_guide TO IDX_34C99C7389D68997');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clothes RENAME INDEX idx_3079b48c89d68997 TO IDX_CLOTHES_SIZE_GUIDE');
        $this->addSql('ALTER TABLE measurement_type CHANGE code code VARCHAR(80) NOT NULL');
        $this->addSql('ALTER TABLE size_guide ADD default_unit VARCHAR(20) NOT NULL, DROP unit');
        $this->addSql('ALTER TABLE size_guide_measurement DROP FOREIGN KEY FK_41D7B38DFBAAC0F');
        $this->addSql('DROP INDEX IDX_41D7B38DFBAAC0F ON size_guide_measurement');
        $this->addSql('DROP INDEX UNIQ_SIZE_GUIDE_MEASUREMENT_TYPE ON size_guide_measurement');
        $this->addSql('ALTER TABLE size_guide_measurement CHANGE value value NUMERIC(8, 2) NOT NULL, CHANGE unit unit VARCHAR(20) NOT NULL, CHANGE size_guide_size_id size_id INT NOT NULL');
        $this->addSql('ALTER TABLE size_guide_measurement ADD CONSTRAINT `FK_SIZE_GUIDE_MEASUREMENT_SIZE` FOREIGN KEY (size_id) REFERENCES size_guide_size (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_SIZE_GUIDE_MEASUREMENT_SIZE ON size_guide_measurement (size_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SIZE_GUIDE_MEASUREMENT_TYPE ON size_guide_measurement (size_id, type_id)');
        $this->addSql('ALTER TABLE size_guide_measurement RENAME INDEX idx_41d7b38dc54c8c93 TO IDX_SIZE_GUIDE_MEASUREMENT_TYPE');
        $this->addSql('ALTER TABLE size_guide_size DROP FOREIGN KEY FK_34C99C7389D68997');
        $this->addSql('ALTER TABLE size_guide_size CHANGE label label VARCHAR(40) NOT NULL');
        $this->addSql('ALTER TABLE size_guide_size ADD CONSTRAINT `FK_SIZE_GUIDE_SIZE_GUIDE` FOREIGN KEY (size_guide_id) REFERENCES size_guide (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE size_guide_size RENAME INDEX idx_34c99c7389d68997 TO IDX_SIZE_GUIDE_SIZE_GUIDE');
    }
}
