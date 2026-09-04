<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add variant-specific gallery, highlight and bestseller images.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clothes_variant ADD images JSON DEFAULT NULL, ADD highlight_image VARCHAR(255) DEFAULT NULL, ADD bestseller_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE clothes_variant cv INNER JOIN clothes c ON c.id = cv.clothes_id SET cv.images = c.images, cv.highlight_image = JSON_UNQUOTE(JSON_EXTRACT(c.images, \'$[0]\')), cv.bestseller_image = JSON_UNQUOTE(JSON_EXTRACT(c.images, \'$[0]\')) WHERE cv.images IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clothes_variant DROP images, DROP highlight_image, DROP bestseller_image');
    }
}
