<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260807194001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace measurement type codes with UUIDs and cascade measurement deletion.';
    }

    public function up(Schema $schema): void
    {
        $measurementType = $schema->getTable('measurement_type');
        $measurementTypeIds = $this->connection->fetchFirstColumn(
            'SELECT id FROM measurement_type ORDER BY id',
        );

        if (!$measurementType->hasColumn('uuid')) {
            $this->addSql('ALTER TABLE measurement_type ADD uuid BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        }

        foreach ($measurementTypeIds as $measurementTypeId) {
            $this->addSql(
                'UPDATE measurement_type SET uuid = UUID_TO_BIN(UUID()) WHERE id = ?',
                [(int) $measurementTypeId],
            );
        }

        if ($measurementType->hasIndex('UNIQ_MEASUREMENT_TYPE_CODE')) {
            $this->addSql('DROP INDEX UNIQ_MEASUREMENT_TYPE_CODE ON measurement_type');
        }

        $columnsToDrop = [];

        if ($measurementType->hasColumn('code')) {
            $columnsToDrop[] = 'DROP code';
        }

        if ($measurementType->hasColumn('is_active')) {
            $columnsToDrop[] = 'DROP is_active';
        }

        $alterOperations = array_merge(
            ['CHANGE uuid uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\''],
            $columnsToDrop,
        );

        $this->addSql(sprintf('ALTER TABLE measurement_type %s', implode(', ', $alterOperations)));

        $this->addSql('CREATE UNIQUE INDEX UNIQ_FF48B378D17F50A6 ON measurement_type (uuid)');
        $this->addSql('ALTER TABLE size_guide_measurement DROP FOREIGN KEY `FK_41D7B38DC54C8C93`');
        $this->addSql(<<<'SQL'
            ALTER TABLE size_guide_measurement
            ADD CONSTRAINT FK_41D7B38DC54C8C93
            FOREIGN KEY (type_id) REFERENCES measurement_type (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_FF48B378D17F50A6 ON measurement_type');
        $this->addSql(<<<'SQL'
            ALTER TABLE measurement_type
            ADD code VARCHAR(64) DEFAULT NULL,
            ADD is_active TINYINT(1) DEFAULT 1 NOT NULL
        SQL);
        $this->addSql("UPDATE measurement_type SET code = CONCAT('measurement_', id)");
        $this->addSql('ALTER TABLE measurement_type CHANGE code code VARCHAR(64) NOT NULL, DROP uuid');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MEASUREMENT_TYPE_CODE ON measurement_type (code)');
        $this->addSql('ALTER TABLE size_guide_measurement DROP FOREIGN KEY FK_41D7B38DC54C8C93');
        $this->addSql(<<<'SQL'
            ALTER TABLE size_guide_measurement
            ADD CONSTRAINT FK_41D7B38DC54C8C93
            FOREIGN KEY (type_id) REFERENCES measurement_type (id)
            ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
    }
}
