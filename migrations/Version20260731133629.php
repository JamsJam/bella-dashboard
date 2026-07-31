<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260731133629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, review_uuid VARCHAR(36) NOT NULL, rating INT UNSIGNED DEFAULT NULL, comment VARCHAR(200) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, requested_at DATETIME NOT NULL, reply VARCHAR(200) DEFAULT NULL, status VARCHAR(255) NOT NULL, reply_at DATETIME DEFAULT NULL, product_id INT NOT NULL, order_id INT NOT NULL, customers_id INT NOT NULL, INDEX IDX_794381C64584665A (product_id), INDEX IDX_794381C68D9F6D38 (order_id), INDEX IDX_794381C6C3568B40 (customers_id), UNIQUE INDEX UNIQ_REVIEW_UUID (review_uuid), UNIQUE INDEX UNIQ_REVIEW_ORDER_PRODUCT_CUSTOMER (product_id, order_id, customers_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C64584665A FOREIGN KEY (product_id) REFERENCES clothes_variant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C68D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6C3568B40 FOREIGN KEY (customers_id) REFERENCES customers (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_CLOTHES_VARIANT_SLUG ON clothes_variant (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C64584665A');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C68D9F6D38');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6C3568B40');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP INDEX IDX_CLOTHES_VARIANT_SLUG ON clothes_variant');
    }
}
