<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add variant-specific description fields.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clothes_variant ADD description LONGTEXT DEFAULT NULL, ADD metadescription VARCHAR(200) DEFAULT NULL');
        $this->addSql('UPDATE clothes_variant cv INNER JOIN clothes c ON c.id = cv.clothes_id SET cv.description = c.description, cv.metadescription = c.metadescription WHERE cv.description IS NULL AND cv.metadescription IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clothes_variant DROP description, DROP metadescription');
    }
}
