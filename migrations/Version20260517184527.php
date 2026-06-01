<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517184527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cart_item (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, name VARCHAR(255) NOT NULL, quantity INT NOT NULL, unit_price_ttc INT NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, cart_id INT NOT NULL, INDEX IDX_F0FE25271AD5CDBF (cart_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id)');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY `FK_BA388B7CFFE9AD6`');
        $this->addSql('DROP INDEX IDX_BA388B7CFFE9AD6 ON cart');
        $this->addSql('ALTER TABLE cart ADD status VARCHAR(40) NOT NULL, ADD currency VARCHAR(3) NOT NULL, ADD subtotal INT NOT NULL, ADD total INT NOT NULL, ADD stripe_checkout_session_id VARCHAR(255) DEFAULT NULL, ADD stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, ADD stripe_invoice_id VARCHAR(255) DEFAULT NULL, ADD stripe_invoice_url VARCHAR(2048) DEFAULT NULL, ADD customer_id INT NOT NULL, DROP orders_id, DROP product_reference, DROP quantity, DROP unit_price_ht, DROP unit_price_ttc');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B79395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id)');
        $this->addSql('CREATE INDEX IDX_BA388B79395C3F3 ON cart (customer_id)');
        $this->addSql('ALTER TABLE orders ADD cart_id INT NOT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE1AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDEE1AD5CDBF ON orders (cart_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25271AD5CDBF');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B79395C3F3');
        $this->addSql('DROP INDEX IDX_BA388B79395C3F3 ON cart');
        $this->addSql('ALTER TABLE cart ADD orders_id INT NOT NULL, ADD product_reference VARCHAR(255) NOT NULL, ADD quantity INT NOT NULL, ADD unit_price_ht INT NOT NULL, ADD unit_price_ttc INT NOT NULL, DROP status, DROP currency, DROP subtotal, DROP total, DROP stripe_checkout_session_id, DROP stripe_payment_intent_id, DROP stripe_invoice_id, DROP stripe_invoice_url, DROP customer_id');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT `FK_BA388B7CFFE9AD6` FOREIGN KEY (orders_id) REFERENCES orders (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_BA388B7CFFE9AD6 ON cart (orders_id)');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE1AD5CDBF');
        $this->addSql('DROP INDEX UNIQ_E52FFDEE1AD5CDBF ON orders');
        $this->addSql('ALTER TABLE orders DROP cart_id');
    }
}
