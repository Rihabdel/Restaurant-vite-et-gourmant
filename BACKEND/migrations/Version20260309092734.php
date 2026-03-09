<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309092734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE allergens (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, icon VARCHAR(50) DEFAULT NULL, description LONGTEXT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_msg (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(100) NOT NULL, message LONGTEXT NOT NULL, email VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_95B03E97A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dish_allergen (id INT AUTO_INCREMENT NOT NULL, dish_id INT DEFAULT NULL, allergen_id INT DEFAULT NULL, INDEX IDX_3C4389A5148EB0CB (dish_id), INDEX IDX_3C4389A56E775A4A (allergen_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dishes (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, description LONGTEXT NOT NULL, price NUMERIC(10, 2) NOT NULL, category VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE menus (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description_menu LONGTEXT NOT NULL, picture LONGBLOB DEFAULT NULL, min_people INT NOT NULL, price NUMERIC(10, 2) NOT NULL, conditions LONGTEXT NOT NULL, stock INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, theme_menu VARCHAR(255) DEFAULT NULL, diet_menu VARCHAR(255) DEFAULT NULL, order_before INT NOT NULL, is_available TINYINT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE menus_dishes (id INT AUTO_INCREMENT NOT NULL, display_order INT NOT NULL, menu_id INT DEFAULT NULL, dish_id INT NOT NULL, INDEX IDX_877C5E79CCD7E912 (menu_id), INDEX IDX_877C5E79148EB0CB (dish_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, number_of_people INT NOT NULL, total_price DOUBLE PRECISION NOT NULL, delivery_cost NUMERIC(10, 0) DEFAULT NULL, delivery_address VARCHAR(255) NOT NULL, delivery_date DATETIME NOT NULL, delivery_time TIME NOT NULL, status VARCHAR(50) NOT NULL, cancellation_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, delivery_city VARCHAR(50) NOT NULL, delivery_postal_code INT NOT NULL, concaled_by VARCHAR(100) DEFAULT NULL, user_id INT NOT NULL, menu_id INT NOT NULL, review_id INT DEFAULT NULL, INDEX IDX_E52FFDEEA76ED395 (user_id), INDEX IDX_E52FFDEECCD7E912 (menu_id), UNIQUE INDEX UNIQ_E52FFDEE3E2E969B (review_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reviews (id INT AUTO_INCREMENT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, is_validated TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, commande_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6970EB0F82EA2E54 (commande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, adress LONGTEXT DEFAULT NULL, phone INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, api_token VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE contact_msg ADD CONSTRAINT FK_95B03E97A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE dish_allergen ADD CONSTRAINT FK_3C4389A5148EB0CB FOREIGN KEY (dish_id) REFERENCES dishes (id)');
        $this->addSql('ALTER TABLE dish_allergen ADD CONSTRAINT FK_3C4389A56E775A4A FOREIGN KEY (allergen_id) REFERENCES allergens (id)');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT FK_877C5E79CCD7E912 FOREIGN KEY (menu_id) REFERENCES menus (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT FK_877C5E79148EB0CB FOREIGN KEY (dish_id) REFERENCES dishes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEECCD7E912 FOREIGN KEY (menu_id) REFERENCES menus (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE3E2E969B FOREIGN KEY (review_id) REFERENCES reviews (id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F82EA2E54 FOREIGN KEY (commande_id) REFERENCES orders (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_msg DROP FOREIGN KEY FK_95B03E97A76ED395');
        $this->addSql('ALTER TABLE dish_allergen DROP FOREIGN KEY FK_3C4389A5148EB0CB');
        $this->addSql('ALTER TABLE dish_allergen DROP FOREIGN KEY FK_3C4389A56E775A4A');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY FK_877C5E79CCD7E912');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY FK_877C5E79148EB0CB');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA76ED395');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEECCD7E912');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE3E2E969B');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F82EA2E54');
        $this->addSql('DROP TABLE allergens');
        $this->addSql('DROP TABLE contact_msg');
        $this->addSql('DROP TABLE dish_allergen');
        $this->addSql('DROP TABLE dishes');
        $this->addSql('DROP TABLE menus');
        $this->addSql('DROP TABLE menus_dishes');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE reviews');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
