<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710115900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop legacy unique index on clothes variant slug.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('clothes_variant')) {
            return;
        }

        $table = $schema->getTable('clothes_variant');
        if (!$table->hasColumn('slug')) {
            return;
        }

        if ($table->hasIndex('UNIQ_CLOTHES_VARIANT_SLUG')) {
            $this->addSql('DROP INDEX UNIQ_CLOTHES_VARIANT_SLUG ON clothes_variant');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('clothes_variant')) {
            return;
        }

        $table = $schema->getTable('clothes_variant');
        if (!$table->hasColumn('slug')) {
            return;
        }

        if (!$table->hasIndex('UNIQ_CLOTHES_VARIANT_SLUG')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_CLOTHES_VARIANT_SLUG ON clothes_variant (slug)');
        }
    }
}
