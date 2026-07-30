<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260730152931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Répare les anciens statuts de commande incompatibles avec OrderStatus.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                UPDATE orders
                SET order_status = CASE
                    WHEN status = 'paid' THEN 'processing'
                    WHEN status = 'payment_expired' THEN 'cancelled'
                    ELSE 'created'
                END
                WHERE order_status IS NULL
                   OR order_status NOT IN (
                       'created',
                       'processing',
                       'cancelled',
                       'awaiting_delivery',
                       'shipped',
                       'delivered'
                   )
                SQL,
        );
    }

    public function down(Schema $schema): void
    {
        // La réparation de données ne doit pas être annulée.
    }
}
