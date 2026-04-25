<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250706212333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE `admin` (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE body (id INT AUTO_INCREMENT NOT NULL, skincolor_id INT NOT NULL, morphotype_id INT NOT NULL, clothe_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_DBA80BB2FD5F3147 (skincolor_id), INDEX IDX_DBA80BB2FA3AA337 (morphotype_id), INDEX IDX_DBA80BB2D554487F (clothe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE bodysize (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(5) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE cart (id INT AUTO_INCREMENT NOT NULL, orders_id INT NOT NULL, product_reference VARCHAR(255) NOT NULL, quantity INT NOT NULL, unit_price_ht INT NOT NULL, unit_price_ttc INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_BA388B7CFFE9AD6 (orders_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) DEFAULT NULL, is_online TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE clothes (id INT AUTO_INCREMENT NOT NULL, collection_id INT NOT NULL, color_id INT NOT NULL, size_id INT DEFAULT NULL, name VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, price INT DEFAULT NULL, stock INT DEFAULT NULL, images JSON DEFAULT NULL, metadescription LONGTEXT DEFAULT NULL, sku VARCHAR(100) NOT NULL, slug VARCHAR(70) NOT NULL, status VARCHAR(40) NOT NULL, is_online TINYINT(1) NOT NULL, INDEX IDX_3079B48C514956FD (collection_id), INDEX IDX_3079B48C7ADA1FB5 (color_id), INDEX IDX_3079B48C498DA827 (size_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE clothescolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE clothessize (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(5) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE collections (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, name VARCHAR(50) NOT NULL, is_online TINYINT(1) NOT NULL, sizeguid JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_D325D3EE12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE customers (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eye (id INT AUTO_INCREMENT NOT NULL, shape_id INT NOT NULL, color_id INT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_727887B150266CBB (shape_id), INDEX IDX_727887B17ADA1FB5 (color_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eyebrows (id INT AUTO_INCREMENT NOT NULL, shape_id INT NOT NULL, color_id INT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_14DD753750266CBB (shape_id), INDEX IDX_14DD75377ADA1FB5 (color_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eyebrowscolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eyebrowshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eyecolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE eyeshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE faces (id INT AUTO_INCREMENT NOT NULL, skincolor_id INT DEFAULT NULL, shape_id INT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_C8DD2F8BFD5F3147 (skincolor_id), INDEX IDX_C8DD2F8B50266CBB (shape_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE faceshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE hairs (id INT AUTO_INCREMENT NOT NULL, color_id INT NOT NULL, shape_id INT NOT NULL, name VARCHAR(100) NOT NULL, images JSON NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_7FF990AA7ADA1FB5 (color_id), INDEX IDX_7FF990AA50266CBB (shape_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE hairscolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE hairshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE morphologie (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE morphotype (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mouths (id INT AUTO_INCREMENT NOT NULL, shape_id INT NOT NULL, color_id INT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75FB0CB050266CBB (shape_id), INDEX IDX_75FB0CB07ADA1FB5 (color_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mouthscolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mouthshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE nose (id INT AUTO_INCREMENT NOT NULL, skincolor_id INT NOT NULL, shape_id INT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_80FC6CD3FD5F3147 (skincolor_id), INDEX IDX_80FC6CD350266CBB (shape_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE noseshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, subtotal INT NOT NULL, total INT NOT NULL, status VARCHAR(255) NOT NULL, order_reference VARCHAR(255) NOT NULL, fees INT NOT NULL, shippinfo JSON NOT NULL, tva INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_E52FFDEE9395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE skincolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', edited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
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
            ALTER TABLE cart ADD CONSTRAINT FK_BA388B7CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id)
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
            ALTER TABLE hairs ADD CONSTRAINT FK_7FF990AA7ADA1FB5 FOREIGN KEY (color_id) REFERENCES hairscolor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE hairs ADD CONSTRAINT FK_7FF990AA50266CBB FOREIGN KEY (shape_id) REFERENCES hairshape (id)
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
            ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE9395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id)
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
            ALTER TABLE cart DROP FOREIGN KEY FK_BA388B7CFFE9AD6
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
            ALTER TABLE hairs DROP FOREIGN KEY FK_7FF990AA7ADA1FB5
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE hairs DROP FOREIGN KEY FK_7FF990AA50266CBB
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
            ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE9395C3F3
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `admin`
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE body
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE bodysize
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE cart
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
            DROP TABLE customers
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
            DROP TABLE hairs
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE hairscolor
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE hairshape
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
            DROP TABLE orders
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE skincolor
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
