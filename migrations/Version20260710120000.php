<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move remaining clothes fields and body links to clothes variants.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clothes_variant ADD size_guide_id INT DEFAULT NULL, ADD name VARCHAR(70) DEFAULT NULL, ADD slug VARCHAR(70) DEFAULT NULL, ADD is_bestseller TINYINT DEFAULT 0 NOT NULL, ADD is_in_carousel TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX IDX_3D1A38589D68997 ON clothes_variant (size_guide_id)');
        $this->addSql('ALTER TABLE clothes_variant ADD CONSTRAINT FK_3D1A38589D68997 FOREIGN KEY (size_guide_id) REFERENCES size_guide (id) ON DELETE SET NULL');

        $this->addSql('CREATE TEMPORARY TABLE clothes_first_variant AS SELECT MIN(id) AS variant_id, clothes_id FROM clothes_variant GROUP BY clothes_id');
        $this->addSql("UPDATE clothes_variant cv INNER JOIN clothes c ON c.id = cv.clothes_id INNER JOIN clothescolor cc ON cc.id = cv.color_id INNER JOIN clothessize cs ON cs.id = cv.size_id SET cv.name = LEFT(TRIM(CONCAT_WS(' ', c.name, cc.name, cs.name)), 70), cv.slug = LEFT(TRIM(CONCAT_WS('-', c.slug, LOWER(REPLACE(cc.name, ' ', '-')))), 70), cv.size_guide_id = c.size_guide_id, cv.is_bestseller = c.is_bestseller, cv.is_in_carousel = c.is_in_carousel");
        $this->addSql('DROP TEMPORARY TABLE clothes_first_variant');

        $this->addSql("UPDATE clothes_variant SET name = CONCAT('Variant ', id) WHERE name IS NULL OR name = ''");
        $this->addSql("UPDATE clothes_variant SET slug = CONCAT('variant-', id) WHERE slug IS NULL OR slug = ''");
        $this->addSql('ALTER TABLE clothes_variant MODIFY name VARCHAR(70) NOT NULL, MODIFY slug VARCHAR(70) NOT NULL');
        $this->addSql('CREATE INDEX IDX_CLOTHES_VARIANT_SLUG ON clothes_variant (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CLOTHES_NAME ON clothes (name)');

        $this->addSql('CREATE TABLE body_clothes_variant (body_id INT NOT NULL, clothes_variant_id INT NOT NULL, INDEX IDX_54D1E33D9B621D84 (body_id), INDEX IDX_54D1E33D4D890E5B (clothes_variant_id), PRIMARY KEY(body_id, clothes_variant_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE body_clothes_variant ADD CONSTRAINT FK_54D1E33D9B621D84 FOREIGN KEY (body_id) REFERENCES body (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE body_clothes_variant ADD CONSTRAINT FK_54D1E33D4D890E5B FOREIGN KEY (clothes_variant_id) REFERENCES clothes_variant (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO body_clothes_variant (body_id, clothes_variant_id) SELECT DISTINCT bc.body_id, cv.id FROM body_clothes bc INNER JOIN clothes_variant cv ON cv.clothes_id = bc.clothes_id');

        $this->addSql('ALTER TABLE body_clothes DROP FOREIGN KEY FK_D98F0D1A9B621D84');
        $this->addSql('ALTER TABLE body_clothes DROP FOREIGN KEY FK_D98F0D1A271E85C0');
        $this->addSql('DROP TABLE body_clothes');

        $this->addSql('ALTER TABLE clothes DROP FOREIGN KEY FK_3079B48C89D68997');
        $this->addSql('DROP INDEX UNIQ_CLOTHES_SLUG ON clothes');
        $this->addSql('DROP INDEX IDX_3079B48C89D68997 ON clothes');
        $this->addSql('ALTER TABLE clothes DROP description, DROP images, DROP metadescription, DROP slug, DROP status, DROP is_bestseller, DROP is_in_carousel, DROP size_guide_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clothes ADD description LONGTEXT DEFAULT NULL, ADD images JSON DEFAULT NULL, ADD metadescription VARCHAR(200) DEFAULT NULL, ADD slug VARCHAR(70) DEFAULT NULL, ADD status VARCHAR(40) DEFAULT NULL, ADD is_bestseller TINYINT DEFAULT 0 NOT NULL, ADD is_in_carousel TINYINT DEFAULT 0 NOT NULL, ADD size_guide_id INT DEFAULT NULL');

        $this->addSql('CREATE TEMPORARY TABLE clothes_first_variant AS SELECT MIN(id) AS variant_id, clothes_id FROM clothes_variant GROUP BY clothes_id');
        $this->addSql("UPDATE clothes c INNER JOIN clothes_first_variant first_variant ON first_variant.clothes_id = c.id INNER JOIN clothes_variant cv ON cv.id = first_variant.variant_id SET c.description = cv.description, c.images = cv.images, c.metadescription = cv.metadescription, c.slug = cv.slug, c.status = 'draft', c.is_bestseller = cv.is_bestseller, c.is_in_carousel = cv.is_in_carousel, c.size_guide_id = cv.size_guide_id");
        $this->addSql('DROP TEMPORARY TABLE clothes_first_variant');

        $this->addSql("UPDATE clothes SET slug = CONCAT('restored-', id) WHERE slug IS NULL OR slug = ''");
        $this->addSql("UPDATE clothes SET status = 'draft' WHERE status IS NULL OR status = ''");
        $this->addSql('ALTER TABLE clothes MODIFY slug VARCHAR(70) NOT NULL, MODIFY status VARCHAR(40) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CLOTHES_SLUG ON clothes (slug)');
        $this->addSql('CREATE INDEX IDX_3079B48C89D68997 ON clothes (size_guide_id)');
        $this->addSql('ALTER TABLE clothes ADD CONSTRAINT FK_3079B48C89D68997 FOREIGN KEY (size_guide_id) REFERENCES size_guide (id) ON DELETE SET NULL');

        $this->addSql('CREATE TABLE body_clothes (body_id INT NOT NULL, clothes_id INT NOT NULL, INDEX IDX_D98F0D1A9B621D84 (body_id), INDEX IDX_D98F0D1A271E85C0 (clothes_id), PRIMARY KEY(body_id, clothes_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE body_clothes ADD CONSTRAINT FK_D98F0D1A9B621D84 FOREIGN KEY (body_id) REFERENCES body (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE body_clothes ADD CONSTRAINT FK_D98F0D1A271E85C0 FOREIGN KEY (clothes_id) REFERENCES clothes (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO body_clothes (body_id, clothes_id) SELECT DISTINCT bcv.body_id, cv.clothes_id FROM body_clothes_variant bcv INNER JOIN clothes_variant cv ON cv.id = bcv.clothes_variant_id');

        $this->addSql('ALTER TABLE body_clothes_variant DROP FOREIGN KEY FK_54D1E33D9B621D84');
        $this->addSql('ALTER TABLE body_clothes_variant DROP FOREIGN KEY FK_54D1E33D4D890E5B');
        $this->addSql('DROP TABLE body_clothes_variant');

        $this->addSql('ALTER TABLE clothes_variant DROP FOREIGN KEY FK_3D1A38589D68997');
        $this->addSql('DROP INDEX UNIQ_CLOTHES_NAME ON clothes');
        $this->addSql('DROP INDEX IDX_CLOTHES_VARIANT_SLUG ON clothes_variant');
        $this->addSql('DROP INDEX IDX_3D1A38589D68997 ON clothes_variant');
        $this->addSql('ALTER TABLE clothes_variant DROP size_guide_id, DROP name, DROP slug, DROP is_bestseller, DROP is_in_carousel');
    }
}
