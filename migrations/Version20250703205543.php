<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250703205543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE body (id INT AUTO_INCREMENT NOT NULL, skincolor_id INT NOT NULL, morphotype_id INT NOT NULL, clothe_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(100) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, INDEX IDX_DBA80BB2FD5F3147 (skincolor_id), INDEX IDX_DBA80BB2FA3AA337 (morphotype_id), INDEX IDX_DBA80BB2D554487F (clothe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE bodysize (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(5) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, image VARCHAR(255) DEFAULT NULL, is_online TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE clothes (id INT AUTO_INCREMENT NOT NULL, collection_id INT NOT NULL, color_id INT NOT NULL, size_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, price INT DEFAULT NULL, stock INT DEFAULT NULL, images JSON DEFAULT NULL, metadescription LONGTEXT DEFAULT NULL, sku VARCHAR(100) NOT NULL, slug VARCHAR(70) NOT NULL, status VARCHAR(40) NOT NULL, is_online TINYINT(1) NOT NULL, INDEX IDX_3079B48C514956FD (collection_id), INDEX IDX_3079B48C7ADA1FB5 (color_id), INDEX IDX_3079B48C498DA827 (size_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE clothescolor (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, hexa VARCHAR(6) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE clothessize (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(5) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE collections (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, is_online TINYINT(1) NOT NULL, sizeguid JSON DEFAULT NULL, INDEX IDX_D325D3EE12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eye (id INT AUTO_INCREMENT NOT NULL, shape_id INT NOT NULL, color_id INT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, INDEX IDX_727887B150266CBB (shape_id), INDEX IDX_727887B17ADA1FB5 (color_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eyebrows (id INT AUTO_INCREMENT NOT NULL, shape_id INT NOT NULL, color_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, INDEX IDX_14DD753750266CBB (shape_id), INDEX IDX_14DD75377ADA1FB5 (color_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eyebrowscolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eyebrowshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eyecolor (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eyeshape (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE faces (id INT AUTO_INCREMENT NOT NULL, skincolor_id INT DEFAULT NULL, shape_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, INDEX IDX_C8DD2F8BFD5F3147 (skincolor_id), INDEX IDX_C8DD2F8B50266CBB (shape_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE faceshape (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE hairscolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE morphologie (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE morphotype (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mouths (id INT AUTO_INCREMENT NOT NULL, shape_id INT NOT NULL, color_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, INDEX IDX_75FB0CB050266CBB (shape_id), INDEX IDX_75FB0CB07ADA1FB5 (color_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mouthscolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', hexa VARCHAR(6) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mouthshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE nose (id INT AUTO_INCREMENT NOT NULL, skincolor_id INT NOT NULL, shape_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, INDEX IDX_80FC6CD3FD5F3147 (skincolor_id), INDEX IDX_80FC6CD350266CBB (shape_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE noseshape (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, images JSON DEFAULT NULL, projectlink VARCHAR(255) DEFAULT NULL, is_online TINYINT(1) NOT NULL, is_gitpublic TINYINT(1) NOT NULL, gitlink VARCHAR(255) DEFAULT NULL, casestudy LONGTEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_2FB3D0EE989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_technology (project_id INT NOT NULL, technology_id INT NOT NULL, INDEX IDX_ECC5297F166D1F9C (project_id), INDEX IDX_ECC5297F4235D463 (technology_id), PRIMARY KEY(project_id, technology_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE skincolor (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag_project (tag_id INT NOT NULL, project_id INT NOT NULL, INDEX IDX_1D82FD44BAD26311 (tag_id), INDEX IDX_1D82FD44166D1F9C (project_id), PRIMARY KEY(tag_id, project_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE technology (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE body ADD CONSTRAINT FK_DBA80BB2FD5F3147 FOREIGN KEY (skincolor_id) REFERENCES skincolor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE body ADD CONSTRAINT FK_DBA80BB2FA3AA337 FOREIGN KEY (morphotype_id) REFERENCES morphotype (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE body ADD CONSTRAINT FK_DBA80BB2D554487F FOREIGN KEY (clothe_id) REFERENCES clothes (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clothes ADD CONSTRAINT FK_3079B48C514956FD FOREIGN KEY (collection_id) REFERENCES collections (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clothes ADD CONSTRAINT FK_3079B48C7ADA1FB5 FOREIGN KEY (color_id) REFERENCES clothescolor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clothes ADD CONSTRAINT FK_3079B48C498DA827 FOREIGN KEY (size_id) REFERENCES clothessize (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collections ADD CONSTRAINT FK_D325D3EE12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE eye ADD CONSTRAINT FK_727887B150266CBB FOREIGN KEY (shape_id) REFERENCES eyeshape (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE eye ADD CONSTRAINT FK_727887B17ADA1FB5 FOREIGN KEY (color_id) REFERENCES eyecolor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE eyebrows ADD CONSTRAINT FK_14DD753750266CBB FOREIGN KEY (shape_id) REFERENCES eyebrowshape (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE eyebrows ADD CONSTRAINT FK_14DD75377ADA1FB5 FOREIGN KEY (color_id) REFERENCES eyebrowscolor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE faces ADD CONSTRAINT FK_C8DD2F8BFD5F3147 FOREIGN KEY (skincolor_id) REFERENCES skincolor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE faces ADD CONSTRAINT FK_C8DD2F8B50266CBB FOREIGN KEY (shape_id) REFERENCES faceshape (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mouths ADD CONSTRAINT FK_75FB0CB050266CBB FOREIGN KEY (shape_id) REFERENCES mouthshape (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mouths ADD CONSTRAINT FK_75FB0CB07ADA1FB5 FOREIGN KEY (color_id) REFERENCES mouthscolor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE nose ADD CONSTRAINT FK_80FC6CD3FD5F3147 FOREIGN KEY (skincolor_id) REFERENCES skincolor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE nose ADD CONSTRAINT FK_80FC6CD350266CBB FOREIGN KEY (shape_id) REFERENCES noseshape (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology ADD CONSTRAINT FK_ECC5297F166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology ADD CONSTRAINT FK_ECC5297F4235D463 FOREIGN KEY (technology_id) REFERENCES technology (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_project ADD CONSTRAINT FK_1D82FD44BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_project ADD CONSTRAINT FK_1D82FD44166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE body DROP FOREIGN KEY FK_DBA80BB2FD5F3147
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE body DROP FOREIGN KEY FK_DBA80BB2FA3AA337
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE body DROP FOREIGN KEY FK_DBA80BB2D554487F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clothes DROP FOREIGN KEY FK_3079B48C514956FD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clothes DROP FOREIGN KEY FK_3079B48C7ADA1FB5
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clothes DROP FOREIGN KEY FK_3079B48C498DA827
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collections DROP FOREIGN KEY FK_D325D3EE12469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE eye DROP FOREIGN KEY FK_727887B150266CBB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE eye DROP FOREIGN KEY FK_727887B17ADA1FB5
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE eyebrows DROP FOREIGN KEY FK_14DD753750266CBB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE eyebrows DROP FOREIGN KEY FK_14DD75377ADA1FB5
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE faces DROP FOREIGN KEY FK_C8DD2F8BFD5F3147
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE faces DROP FOREIGN KEY FK_C8DD2F8B50266CBB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mouths DROP FOREIGN KEY FK_75FB0CB050266CBB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mouths DROP FOREIGN KEY FK_75FB0CB07ADA1FB5
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE nose DROP FOREIGN KEY FK_80FC6CD3FD5F3147
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE nose DROP FOREIGN KEY FK_80FC6CD350266CBB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology DROP FOREIGN KEY FK_ECC5297F166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology DROP FOREIGN KEY FK_ECC5297F4235D463
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_project DROP FOREIGN KEY FK_1D82FD44BAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_project DROP FOREIGN KEY FK_1D82FD44166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE body
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE bodysize
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE clothes
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE clothescolor
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE clothessize
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE collections
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE eye
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE eyebrows
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE eyebrowscolor
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE eyebrowshape
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE eyecolor
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE eyeshape
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE faces
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE faceshape
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE hairscolor
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE morphologie
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE morphotype
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE mouths
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE mouthscolor
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE mouthshape
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE nose
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE noseshape
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE project
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE project_technology
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE skincolor
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag_project
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE technology
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
