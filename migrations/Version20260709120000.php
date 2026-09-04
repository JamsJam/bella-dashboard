<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move clothes color, size, stock and sku into clothes variants.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE clothes_variant (id INT AUTO_INCREMENT NOT NULL, clothes_id INT NOT NULL, color_id INT NOT NULL, size_id INT NOT NULL, sku VARCHAR(100) NOT NULL, stock INT UNSIGNED NOT NULL, is_online TINYINT NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, INDEX IDX_3D1A3858D6E0F4F (clothes_id), INDEX IDX_3D1A38587ADA1FB5 (color_id), INDEX IDX_3D1A3858498DA827 (size_id), UNIQUE INDEX UNIQ_CLOTHES_VARIANT_COMBINATION (clothes_id, color_id, size_id), UNIQUE INDEX UNIQ_CLOTHES_VARIANT_SKU (sku), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE clothes_variant ADD CONSTRAINT FK_3D1A3858D6E0F4F FOREIGN KEY (clothes_id) REFERENCES clothes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE clothes_variant ADD CONSTRAINT FK_3D1A38587ADA1FB5 FOREIGN KEY (color_id) REFERENCES clothescolor (id)');
        $this->addSql('ALTER TABLE clothes_variant ADD CONSTRAINT FK_3D1A3858498DA827 FOREIGN KEY (size_id) REFERENCES clothessize (id)');

        $this->addSql("INSERT INTO clothessize (name, created_at, edited_at) SELECT 'TU', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM clothessize WHERE name = 'TU')");
        $this->addSql('UPDATE clothes SET size_id = (SELECT id FROM clothessize WHERE name = \'TU\' LIMIT 1) WHERE size_id IS NULL');
        $this->addSql('UPDATE clothes SET stock = 0 WHERE stock IS NULL OR stock < 0');

        $this->addSql('CREATE TEMPORARY TABLE clothes_slug_canonical AS SELECT MIN(id) AS canonical_id, slug FROM clothes GROUP BY slug');
        $this->addSql('INSERT INTO clothes_variant (clothes_id, color_id, size_id, sku, stock, is_online, created_at, edited_at) SELECT canonical.canonical_id, c.color_id, c.size_id, c.sku, c.stock, c.is_online, c.created_at, c.edited_at FROM clothes c INNER JOIN clothes_slug_canonical canonical ON canonical.slug = c.slug');
        $this->addSql('CREATE TEMPORARY TABLE clothes_variant_migration_map AS SELECT c.id AS old_clothes_id, cv.id AS variant_id FROM clothes c INNER JOIN clothes_variant cv ON cv.sku = c.sku');

        $this->addSql('ALTER TABLE cart_item ADD variant_id INT DEFAULT NULL');
        $this->addSql('UPDATE cart_item ci INNER JOIN clothes_variant_migration_map map ON map.old_clothes_id = ci.product_id SET ci.variant_id = map.variant_id');
        $this->addSql('CREATE INDEX IDX_F0FE25273B69A9AF ON cart_item (variant_id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25273B69A9AF FOREIGN KEY (variant_id) REFERENCES clothes_variant (id) ON DELETE SET NULL');

        $this->addSql('CREATE TEMPORARY TABLE body_clothes_canonical AS SELECT DISTINCT bc.body_id, canonical.canonical_id AS clothes_id FROM body_clothes bc INNER JOIN clothes c ON c.id = bc.clothes_id INNER JOIN clothes_slug_canonical canonical ON canonical.slug = c.slug');
        $this->addSql('DELETE FROM body_clothes');
        $this->addSql('INSERT INTO body_clothes (body_id, clothes_id) SELECT body_id, clothes_id FROM body_clothes_canonical');

        $this->addSql('DELETE c FROM clothes c INNER JOIN clothes_slug_canonical canonical ON canonical.slug = c.slug WHERE c.id <> canonical.canonical_id');
        $this->addSql('DROP TEMPORARY TABLE body_clothes_canonical');
        $this->addSql('DROP TEMPORARY TABLE clothes_variant_migration_map');
        $this->addSql('DROP TEMPORARY TABLE clothes_slug_canonical');

        $this->addSql('ALTER TABLE clothes DROP FOREIGN KEY FK_3079B48C7ADA1FB5');
        $this->addSql('ALTER TABLE clothes DROP FOREIGN KEY FK_3079B48C498DA827');
        $this->addSql('DROP INDEX IDX_3079B48C7ADA1FB5 ON clothes');
        $this->addSql('DROP INDEX IDX_3079B48C498DA827 ON clothes');
        $this->addSql('ALTER TABLE clothes DROP stock, DROP sku, DROP color_id, DROP size_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CLOTHES_SLUG ON clothes (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clothes ADD stock INT DEFAULT NULL, ADD sku VARCHAR(100) DEFAULT NULL, ADD color_id INT DEFAULT NULL, ADD size_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_CLOTHES_SLUG ON clothes');

        $this->addSql('CREATE TEMPORARY TABLE clothes_first_variant AS SELECT MIN(id) AS variant_id, clothes_id FROM clothes_variant GROUP BY clothes_id');
        $this->addSql('UPDATE clothes c INNER JOIN clothes_first_variant first_variant ON first_variant.clothes_id = c.id INNER JOIN clothes_variant cv ON cv.id = first_variant.variant_id SET c.stock = cv.stock, c.sku = cv.sku, c.color_id = cv.color_id, c.size_id = cv.size_id, c.is_online = cv.is_online');
        $this->addSql('INSERT INTO clothes (name, description, price, stock, images, metadescription, sku, slug, status, is_online, is_bestseller, is_in_carousel, created_at, edited_at, collection_id, color_id, size_id, size_guide_id) SELECT c.name, c.description, c.price, cv.stock, c.images, c.metadescription, cv.sku, c.slug, c.status, cv.is_online, c.is_bestseller, c.is_in_carousel, c.created_at, cv.edited_at, c.collection_id, cv.color_id, cv.size_id, c.size_guide_id FROM clothes_variant cv INNER JOIN clothes c ON c.id = cv.clothes_id LEFT JOIN clothes_first_variant first_variant ON first_variant.variant_id = cv.id WHERE first_variant.variant_id IS NULL');
        $this->addSql('CREATE TEMPORARY TABLE clothes_variant_restore_map AS SELECT cv.id AS variant_id, c.id AS clothes_id, cv.clothes_id AS canonical_clothes_id FROM clothes_variant cv INNER JOIN clothes c ON c.sku = cv.sku');

        $this->addSql('UPDATE cart_item ci INNER JOIN clothes_variant_restore_map map ON map.variant_id = ci.variant_id SET ci.product_id = map.clothes_id WHERE ci.variant_id IS NOT NULL');
        $this->addSql('CREATE TEMPORARY TABLE body_clothes_restore AS SELECT DISTINCT bc.body_id, map.clothes_id FROM body_clothes bc INNER JOIN clothes_variant_restore_map map ON map.canonical_clothes_id = bc.clothes_id');
        $this->addSql('DELETE FROM body_clothes');
        $this->addSql('INSERT INTO body_clothes (body_id, clothes_id) SELECT body_id, clothes_id FROM body_clothes_restore');

        $this->addSql('DROP TEMPORARY TABLE body_clothes_restore');
        $this->addSql('DROP TEMPORARY TABLE clothes_variant_restore_map');
        $this->addSql('DROP TEMPORARY TABLE clothes_first_variant');

        $this->addSql('UPDATE clothes SET sku = CONCAT(\'RESTORED-\', id) WHERE sku IS NULL');
        $this->addSql('ALTER TABLE clothes MODIFY sku VARCHAR(100) NOT NULL, MODIFY color_id INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_3079B48C7ADA1FB5 ON clothes (color_id)');
        $this->addSql('CREATE INDEX IDX_3079B48C498DA827 ON clothes (size_id)');
        $this->addSql('ALTER TABLE clothes ADD CONSTRAINT FK_3079B48C7ADA1FB5 FOREIGN KEY (color_id) REFERENCES clothescolor (id)');
        $this->addSql('ALTER TABLE clothes ADD CONSTRAINT FK_3079B48C498DA827 FOREIGN KEY (size_id) REFERENCES clothessize (id)');

        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25273B69A9AF');
        $this->addSql('DROP INDEX IDX_F0FE25273B69A9AF ON cart_item');
        $this->addSql('ALTER TABLE cart_item DROP variant_id');
        $this->addSql('ALTER TABLE clothes_variant DROP FOREIGN KEY FK_3D1A3858D6E0F4F');
        $this->addSql('ALTER TABLE clothes_variant DROP FOREIGN KEY FK_3D1A38587ADA1FB5');
        $this->addSql('ALTER TABLE clothes_variant DROP FOREIGN KEY FK_3D1A3858498DA827');
        $this->addSql('DROP TABLE clothes_variant');
    }
}
