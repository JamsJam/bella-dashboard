<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260713112445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CLOTHES_NAME ON clothes (name)');
        $this->addSql('DROP INDEX UNIQ_CLOTHES_VARIANT_COMBINATION ON clothes_variant');
        $this->addSql('ALTER TABLE clothes_variant CHANGE is_bestseller is_bestseller TINYINT NOT NULL, CHANGE is_in_carousel is_in_carousel TINYINT NOT NULL');
        $this->addSql('ALTER TABLE clothes_variant RENAME INDEX idx_3d1a3858d6e0f4f TO IDX_D8A94179271E85C0');
        $this->addSql('ALTER TABLE clothes_variant RENAME INDEX idx_3d1a38589d68997 TO IDX_D8A9417989D68997');
        $this->addSql('ALTER TABLE clothes_variant RENAME INDEX idx_3d1a38587ada1fb5 TO IDX_D8A941797ADA1FB5');
        $this->addSql('ALTER TABLE clothes_variant RENAME INDEX idx_3d1a3858498da827 TO IDX_D8A94179498DA827');
        $this->addSql('ALTER TABLE body_clothes_variant DROP PRIMARY KEY, ADD PRIMARY KEY (clothes_variant_id, body_id)');
        $this->addSql('ALTER TABLE body_clothes_variant RENAME INDEX idx_54d1e33d4d890e5b TO IDX_62A4A9D67B09F33F');
        $this->addSql('ALTER TABLE body_clothes_variant RENAME INDEX idx_54d1e33d9b621d84 TO IDX_62A4A9D69B621D84');
        $this->addSql('ALTER TABLE customers ADD is_signup_confirmed TINYINT NOT NULL, ADD signup_verification_code VARCHAR(6) DEFAULT NULL, ADD signup_verification_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE body_clothes_variant DROP PRIMARY KEY, ADD PRIMARY KEY (body_id, clothes_variant_id)');
        $this->addSql('ALTER TABLE body_clothes_variant RENAME INDEX idx_62a4a9d67b09f33f TO IDX_54D1E33D4D890E5B');
        $this->addSql('ALTER TABLE body_clothes_variant RENAME INDEX idx_62a4a9d69b621d84 TO IDX_54D1E33D9B621D84');
        $this->addSql('DROP INDEX UNIQ_CLOTHES_NAME ON clothes');
        $this->addSql('ALTER TABLE clothes_variant CHANGE is_bestseller is_bestseller TINYINT DEFAULT 0 NOT NULL, CHANGE is_in_carousel is_in_carousel TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CLOTHES_VARIANT_COMBINATION ON clothes_variant (clothes_id, color_id, size_id)');
        $this->addSql('ALTER TABLE clothes_variant RENAME INDEX idx_d8a94179498da827 TO IDX_3D1A3858498DA827');
        $this->addSql('ALTER TABLE clothes_variant RENAME INDEX idx_d8a941797ada1fb5 TO IDX_3D1A38587ADA1FB5');
        $this->addSql('ALTER TABLE clothes_variant RENAME INDEX idx_d8a9417989d68997 TO IDX_3D1A38589D68997');
        $this->addSql('ALTER TABLE clothes_variant RENAME INDEX idx_d8a94179271e85c0 TO IDX_3D1A3858D6E0F4F');
        $this->addSql('ALTER TABLE customers DROP is_signup_confirmed, DROP signup_verification_code, DROP signup_verification_expires_at');
    }
}
