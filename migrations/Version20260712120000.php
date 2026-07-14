<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prepare the clothes name index before Version20260713112445 recreates it.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on MySQL.',
        );

        $schemaManager = $this->connection->createSchemaManager();
        $customersTable = $schemaManager->introspectTableByUnquotedName('customers');

        // Version20260713112445 est déjà passée sur cet environnement.
        if ($customersTable->hasColumn('is_signup_confirmed')) {
            return;
        }

        $clothesTable = $schemaManager->introspectTableByUnquotedName('clothes');
        if ($clothesTable->hasIndex('UNIQ_CLOTHES_NAME')) {
            $this->addSql('DROP INDEX UNIQ_CLOTHES_NAME ON clothes');
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on MySQL.',
        );

        $clothesTable = $this->connection->createSchemaManager()->introspectTableByUnquotedName('clothes');
        if (!$clothesTable->hasIndex('UNIQ_CLOTHES_NAME')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_CLOTHES_NAME ON clothes (name)');
        }
    }
}
