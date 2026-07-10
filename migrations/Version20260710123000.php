<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique index on clothes variant name.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CLOTHES_VARIANT_NAME ON clothes_variant (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CLOTHES_VARIANT_NAME ON clothes_variant');
    }
}
