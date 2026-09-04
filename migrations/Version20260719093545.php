<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260719093545 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart DROP status, DROP stripe_checkout_session_id, DROP stripe_payment_intent_id, DROP stripe_invoice_id, DROP stripe_invoice_url');
        $this->addSql('ALTER TABLE orders ADD stripe_checkout_session_id VARCHAR(255) DEFAULT NULL, ADD stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, ADD stripe_invoice_id VARCHAR(255) DEFAULT NULL, ADD stripe_invoice_url VARCHAR(2048) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDEE5A18FBC7 ON orders (stripe_checkout_session_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart ADD status VARCHAR(40) NOT NULL, ADD stripe_checkout_session_id VARCHAR(255) DEFAULT NULL, ADD stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, ADD stripe_invoice_id VARCHAR(255) DEFAULT NULL, ADD stripe_invoice_url VARCHAR(2048) DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_E52FFDEE5A18FBC7 ON orders');
        $this->addSql('ALTER TABLE orders DROP stripe_checkout_session_id, DROP stripe_payment_intent_id, DROP stripe_invoice_id, DROP stripe_invoice_url');
    }
}
