<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260613002917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `admin` (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE avatar_temp (id INT AUTO_INCREMENT NOT NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(255) NOT NULL, relative_path VARCHAR(500) DEFAULT NULL, temp_path VARCHAR(1024) NOT NULL, mime_type VARCHAR(100) DEFAULT NULL, file_size INT NOT NULL, extension VARCHAR(20) NOT NULL, status VARCHAR(30) DEFAULT \'uploaded\' NOT NULL, final_name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE body (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, skincolor_id INT NOT NULL, morphotype_id INT NOT NULL, clothe_id INT DEFAULT NULL, INDEX IDX_DBA80BB2FD5F3147 (skincolor_id), INDEX IDX_DBA80BB2FA3AA337 (morphotype_id), INDEX IDX_DBA80BB2D554487F (clothe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bodysize (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(5) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cart (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(40) NOT NULL, currency VARCHAR(3) NOT NULL, subtotal INT NOT NULL, total INT NOT NULL, stripe_checkout_session_id VARCHAR(255) DEFAULT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, stripe_invoice_id VARCHAR(255) DEFAULT NULL, stripe_invoice_url VARCHAR(2048) DEFAULT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, customer_id INT NOT NULL, INDEX IDX_BA388B79395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cart_item (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, name VARCHAR(255) NOT NULL, quantity INT NOT NULL, unit_price_ttc INT NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, cart_id INT NOT NULL, INDEX IDX_F0FE25271AD5CDBF (cart_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) DEFAULT NULL, meta_description VARCHAR(120) DEFAULT NULL, slug VARCHAR(70) NOT NULL, is_online TINYINT NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE clothes (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(70) NOT NULL, description LONGTEXT DEFAULT NULL, price INT DEFAULT NULL, stock INT DEFAULT NULL, images JSON DEFAULT NULL, metadescription VARCHAR(200) DEFAULT NULL, sku VARCHAR(100) NOT NULL, slug VARCHAR(70) NOT NULL, status VARCHAR(40) NOT NULL, is_online TINYINT NOT NULL, is_bestseller TINYINT NOT NULL, is_in_carousel TINYINT NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, collection_id INT NOT NULL, color_id INT NOT NULL, size_id INT DEFAULT NULL, size_guide_id INT DEFAULT NULL, INDEX IDX_3079B48C514956FD (collection_id), INDEX IDX_3079B48C7ADA1FB5 (color_id), INDEX IDX_3079B48C498DA827 (size_id), INDEX IDX_3079B48C89D68997 (size_guide_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE clothescolor (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE clothessize (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(5) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE collections (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, is_online TINYINT NOT NULL, image VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, category_id INT NOT NULL, INDEX IDX_D325D3EE12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customers (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE eye (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, shape_id INT NOT NULL, color_id INT NOT NULL, INDEX IDX_727887B150266CBB (shape_id), INDEX IDX_727887B17ADA1FB5 (color_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE eyebrows (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, shape_id INT NOT NULL, color_id INT NOT NULL, INDEX IDX_14DD753750266CBB (shape_id), INDEX IDX_14DD75377ADA1FB5 (color_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE eyebrowscolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE eyebrowshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE eyecolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE eyeshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE faces (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, skincolor_id INT DEFAULT NULL, shape_id INT NOT NULL, INDEX IDX_C8DD2F8BFD5F3147 (skincolor_id), INDEX IDX_C8DD2F8B50266CBB (shape_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE faceshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE hairs (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, images JSON NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, color_id INT NOT NULL, shape_id INT NOT NULL, INDEX IDX_7FF990AA7ADA1FB5 (color_id), INDEX IDX_7FF990AA50266CBB (shape_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE hairscolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE hairshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE measurement_type (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(64) NOT NULL, label VARCHAR(120) NOT NULL, description LONGTEXT DEFAULT NULL, position INT NOT NULL, is_active TINYINT NOT NULL, UNIQUE INDEX UNIQ_MEASUREMENT_TYPE_CODE (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE morphologie (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE morphotype (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, size_id INT NOT NULL, morphologie_id INT NOT NULL, INDEX IDX_140B0A9B498DA827 (size_id), INDEX IDX_140B0A9B5A377682 (morphologie_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mouths (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, shape_id INT NOT NULL, color_id INT NOT NULL, INDEX IDX_75FB0CB050266CBB (shape_id), INDEX IDX_75FB0CB07ADA1FB5 (color_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mouthscolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mouthshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE nose (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, image VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, skincolor_id INT NOT NULL, shape_id INT NOT NULL, INDEX IDX_80FC6CD3FD5F3147 (skincolor_id), INDEX IDX_80FC6CD350266CBB (shape_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE noseshape (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, subtotal INT NOT NULL, total INT NOT NULL, status VARCHAR(255) NOT NULL, order_reference VARCHAR(255) NOT NULL, fees INT NOT NULL, shippinfo JSON NOT NULL, tva INT NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, customer_id INT DEFAULT NULL, cart_id INT NOT NULL, INDEX IDX_E52FFDEE9395C3F3 (customer_id), UNIQUE INDEX UNIQ_E52FFDEE1AD5CDBF (cart_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE size_guide (id INT AUTO_INCREMENT NOT NULL, unit VARCHAR(8) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE size_guide_measurement (id INT AUTO_INCREMENT NOT NULL, value NUMERIC(6, 2) NOT NULL, unit VARCHAR(8) NOT NULL, type_id INT NOT NULL, size_guide_size_id INT NOT NULL, INDEX IDX_41D7B38DC54C8C93 (type_id), INDEX IDX_41D7B38DFBAAC0F (size_guide_size_id), UNIQUE INDEX UNIQ_SIZE_GUIDE_MEASUREMENT_TYPE (size_guide_size_id, type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE size_guide_size (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(32) NOT NULL, position INT NOT NULL, size_guide_id INT NOT NULL, INDEX IDX_34C99C7389D68997 (size_guide_id), UNIQUE INDEX UNIQ_SIZE_GUIDE_SIZE_LABEL (size_guide_id, label), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE skincolor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hexa VARCHAR(6) DEFAULT NULL, created_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE body ADD CONSTRAINT FK_DBA80BB2FD5F3147 FOREIGN KEY (skincolor_id) REFERENCES skincolor (id)');
        $this->addSql('ALTER TABLE body ADD CONSTRAINT FK_DBA80BB2FA3AA337 FOREIGN KEY (morphotype_id) REFERENCES morphotype (id)');
        $this->addSql('ALTER TABLE body ADD CONSTRAINT FK_DBA80BB2D554487F FOREIGN KEY (clothe_id) REFERENCES clothes (id)');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B79395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id)');
        $this->addSql('ALTER TABLE clothes ADD CONSTRAINT FK_3079B48C514956FD FOREIGN KEY (collection_id) REFERENCES collections (id)');
        $this->addSql('ALTER TABLE clothes ADD CONSTRAINT FK_3079B48C7ADA1FB5 FOREIGN KEY (color_id) REFERENCES clothescolor (id)');
        $this->addSql('ALTER TABLE clothes ADD CONSTRAINT FK_3079B48C498DA827 FOREIGN KEY (size_id) REFERENCES clothessize (id)');
        $this->addSql('ALTER TABLE clothes ADD CONSTRAINT FK_3079B48C89D68997 FOREIGN KEY (size_guide_id) REFERENCES size_guide (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE collections ADD CONSTRAINT FK_D325D3EE12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE eye ADD CONSTRAINT FK_727887B150266CBB FOREIGN KEY (shape_id) REFERENCES eyeshape (id)');
        $this->addSql('ALTER TABLE eye ADD CONSTRAINT FK_727887B17ADA1FB5 FOREIGN KEY (color_id) REFERENCES eyecolor (id)');
        $this->addSql('ALTER TABLE eyebrows ADD CONSTRAINT FK_14DD753750266CBB FOREIGN KEY (shape_id) REFERENCES eyebrowshape (id)');
        $this->addSql('ALTER TABLE eyebrows ADD CONSTRAINT FK_14DD75377ADA1FB5 FOREIGN KEY (color_id) REFERENCES eyebrowscolor (id)');
        $this->addSql('ALTER TABLE faces ADD CONSTRAINT FK_C8DD2F8BFD5F3147 FOREIGN KEY (skincolor_id) REFERENCES skincolor (id)');
        $this->addSql('ALTER TABLE faces ADD CONSTRAINT FK_C8DD2F8B50266CBB FOREIGN KEY (shape_id) REFERENCES faceshape (id)');
        $this->addSql('ALTER TABLE hairs ADD CONSTRAINT FK_7FF990AA7ADA1FB5 FOREIGN KEY (color_id) REFERENCES hairscolor (id)');
        $this->addSql('ALTER TABLE hairs ADD CONSTRAINT FK_7FF990AA50266CBB FOREIGN KEY (shape_id) REFERENCES hairshape (id)');
        $this->addSql('ALTER TABLE morphotype ADD CONSTRAINT FK_140B0A9B498DA827 FOREIGN KEY (size_id) REFERENCES bodysize (id)');
        $this->addSql('ALTER TABLE morphotype ADD CONSTRAINT FK_140B0A9B5A377682 FOREIGN KEY (morphologie_id) REFERENCES morphologie (id)');
        $this->addSql('ALTER TABLE mouths ADD CONSTRAINT FK_75FB0CB050266CBB FOREIGN KEY (shape_id) REFERENCES mouthshape (id)');
        $this->addSql('ALTER TABLE mouths ADD CONSTRAINT FK_75FB0CB07ADA1FB5 FOREIGN KEY (color_id) REFERENCES mouthscolor (id)');
        $this->addSql('ALTER TABLE nose ADD CONSTRAINT FK_80FC6CD3FD5F3147 FOREIGN KEY (skincolor_id) REFERENCES skincolor (id)');
        $this->addSql('ALTER TABLE nose ADD CONSTRAINT FK_80FC6CD350266CBB FOREIGN KEY (shape_id) REFERENCES noseshape (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE9395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE1AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id)');
        $this->addSql('ALTER TABLE size_guide_measurement ADD CONSTRAINT FK_41D7B38DC54C8C93 FOREIGN KEY (type_id) REFERENCES measurement_type (id)');
        $this->addSql('ALTER TABLE size_guide_measurement ADD CONSTRAINT FK_41D7B38DFBAAC0F FOREIGN KEY (size_guide_size_id) REFERENCES size_guide_size (id)');
        $this->addSql('ALTER TABLE size_guide_size ADD CONSTRAINT FK_34C99C7389D68997 FOREIGN KEY (size_guide_id) REFERENCES size_guide (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE body DROP FOREIGN KEY FK_DBA80BB2FD5F3147');
        $this->addSql('ALTER TABLE body DROP FOREIGN KEY FK_DBA80BB2FA3AA337');
        $this->addSql('ALTER TABLE body DROP FOREIGN KEY FK_DBA80BB2D554487F');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B79395C3F3');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25271AD5CDBF');
        $this->addSql('ALTER TABLE clothes DROP FOREIGN KEY FK_3079B48C514956FD');
        $this->addSql('ALTER TABLE clothes DROP FOREIGN KEY FK_3079B48C7ADA1FB5');
        $this->addSql('ALTER TABLE clothes DROP FOREIGN KEY FK_3079B48C498DA827');
        $this->addSql('ALTER TABLE clothes DROP FOREIGN KEY FK_3079B48C89D68997');
        $this->addSql('ALTER TABLE collections DROP FOREIGN KEY FK_D325D3EE12469DE2');
        $this->addSql('ALTER TABLE eye DROP FOREIGN KEY FK_727887B150266CBB');
        $this->addSql('ALTER TABLE eye DROP FOREIGN KEY FK_727887B17ADA1FB5');
        $this->addSql('ALTER TABLE eyebrows DROP FOREIGN KEY FK_14DD753750266CBB');
        $this->addSql('ALTER TABLE eyebrows DROP FOREIGN KEY FK_14DD75377ADA1FB5');
        $this->addSql('ALTER TABLE faces DROP FOREIGN KEY FK_C8DD2F8BFD5F3147');
        $this->addSql('ALTER TABLE faces DROP FOREIGN KEY FK_C8DD2F8B50266CBB');
        $this->addSql('ALTER TABLE hairs DROP FOREIGN KEY FK_7FF990AA7ADA1FB5');
        $this->addSql('ALTER TABLE hairs DROP FOREIGN KEY FK_7FF990AA50266CBB');
        $this->addSql('ALTER TABLE morphotype DROP FOREIGN KEY FK_140B0A9B498DA827');
        $this->addSql('ALTER TABLE morphotype DROP FOREIGN KEY FK_140B0A9B5A377682');
        $this->addSql('ALTER TABLE mouths DROP FOREIGN KEY FK_75FB0CB050266CBB');
        $this->addSql('ALTER TABLE mouths DROP FOREIGN KEY FK_75FB0CB07ADA1FB5');
        $this->addSql('ALTER TABLE nose DROP FOREIGN KEY FK_80FC6CD3FD5F3147');
        $this->addSql('ALTER TABLE nose DROP FOREIGN KEY FK_80FC6CD350266CBB');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE9395C3F3');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE1AD5CDBF');
        $this->addSql('ALTER TABLE size_guide_measurement DROP FOREIGN KEY FK_41D7B38DC54C8C93');
        $this->addSql('ALTER TABLE size_guide_measurement DROP FOREIGN KEY FK_41D7B38DFBAAC0F');
        $this->addSql('ALTER TABLE size_guide_size DROP FOREIGN KEY FK_34C99C7389D68997');
        $this->addSql('DROP TABLE `admin`');
        $this->addSql('DROP TABLE avatar_temp');
        $this->addSql('DROP TABLE body');
        $this->addSql('DROP TABLE bodysize');
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE clothes');
        $this->addSql('DROP TABLE clothescolor');
        $this->addSql('DROP TABLE clothessize');
        $this->addSql('DROP TABLE collections');
        $this->addSql('DROP TABLE customers');
        $this->addSql('DROP TABLE eye');
        $this->addSql('DROP TABLE eyebrows');
        $this->addSql('DROP TABLE eyebrowscolor');
        $this->addSql('DROP TABLE eyebrowshape');
        $this->addSql('DROP TABLE eyecolor');
        $this->addSql('DROP TABLE eyeshape');
        $this->addSql('DROP TABLE faces');
        $this->addSql('DROP TABLE faceshape');
        $this->addSql('DROP TABLE hairs');
        $this->addSql('DROP TABLE hairscolor');
        $this->addSql('DROP TABLE hairshape');
        $this->addSql('DROP TABLE measurement_type');
        $this->addSql('DROP TABLE morphologie');
        $this->addSql('DROP TABLE morphotype');
        $this->addSql('DROP TABLE mouths');
        $this->addSql('DROP TABLE mouthscolor');
        $this->addSql('DROP TABLE mouthshape');
        $this->addSql('DROP TABLE nose');
        $this->addSql('DROP TABLE noseshape');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE size_guide');
        $this->addSql('DROP TABLE size_guide_measurement');
        $this->addSql('DROP TABLE size_guide_size');
        $this->addSql('DROP TABLE skincolor');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
