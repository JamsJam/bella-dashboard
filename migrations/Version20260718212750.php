<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718212750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise les noms des vêtements, fusionne les parents homonymes et recalcule les slugs sans créer de variant.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on MySQL.',
        );

        $this->addSql("CREATE TEMPORARY TABLE clothes_name_normalization AS SELECT id, LOWER(SUBSTRING_INDEX(TRIM(name), ' ', 1)) AS normalized_name FROM clothes");
        $this->addSql('CREATE INDEX IDX_CLOTHES_NAME_NORMALIZATION_NAME ON clothes_name_normalization (normalized_name)');
        $this->addSql('CREATE TEMPORARY TABLE clothes_name_canonical AS SELECT normalization.normalized_name, COALESCE(MIN(CASE WHEN clothes.name = normalization.normalized_name THEN clothes.id END), MIN(clothes.id)) AS canonical_id FROM clothes_name_normalization normalization INNER JOIN clothes ON clothes.id = normalization.id GROUP BY normalization.normalized_name');
        $this->addSql('UPDATE clothes_variant variant INNER JOIN clothes_name_normalization normalization ON normalization.id = variant.clothes_id INNER JOIN clothes_name_canonical canonical ON canonical.normalized_name = normalization.normalized_name SET variant.clothes_id = canonical.canonical_id WHERE variant.clothes_id <> canonical.canonical_id');
        $this->addSql('DELETE clothes FROM clothes INNER JOIN clothes_name_normalization normalization ON normalization.id = clothes.id INNER JOIN clothes_name_canonical canonical ON canonical.normalized_name = normalization.normalized_name WHERE clothes.id <> canonical.canonical_id');
        $this->addSql('UPDATE clothes INNER JOIN clothes_name_canonical canonical ON canonical.canonical_id = clothes.id SET clothes.name = canonical.normalized_name, clothes.edited_at = NOW()');
        $this->addSql("UPDATE clothes_variant variant INNER JOIN clothes ON clothes.id = variant.clothes_id INNER JOIN clothescolor color ON color.id = variant.color_id SET variant.slug = LEFT(LOWER(REPLACE(TRIM(CONCAT_WS(' ', clothes.name, color.name)), ' ', '-')), 70), variant.edited_at = NOW()");
        $this->addSql('DROP TEMPORARY TABLE clothes_name_canonical');
        $this->addSql('DROP TEMPORARY TABLE clothes_name_normalization');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Les anciens noms et les parents fusionnés ne peuvent pas être reconstruits automatiquement.',
        );
    }
}
