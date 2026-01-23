<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123190524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE allergens (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, icon VARCHAR(50) DEFAULT NULL, description LONGTEXT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE menus_dishes (id INT AUTO_INCREMENT NOT NULL, display_order INT NOT NULL, menu_id_id INT DEFAULT NULL, dishes_id_id INT DEFAULT NULL, INDEX IDX_877C5E79EEE8BD30 (menu_id_id), INDEX IDX_877C5E7980E89383 (dishes_id_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, order_number VARCHAR(20) NOT NULL, people_count INT NOT NULL, delevery_date DATE NOT NULL, delevery_time TIME NOT NULL, delevery_adress VARCHAR(100) NOT NULL, delevery_city VARCHAR(100) NOT NULL, delevery_postal_code VARCHAR(10) NOT NULL, menu_price NUMERIC(10, 2) NOT NULL, delevery_fee NUMERIC(10, 2) DEFAULT NULL, discount NUMERIC(10, 2) DEFAULT NULL, total_price NUMERIC(10, 2) NOT NULL, concaled_by VARCHAR(50) NOT NULL, cancel_reason VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT FK_877C5E79EEE8BD30 FOREIGN KEY (menu_id_id) REFERENCES menus (id)');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT FK_877C5E7980E89383 FOREIGN KEY (dishes_id_id) REFERENCES dishes (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY FK_877C5E79EEE8BD30');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY FK_877C5E7980E89383');
        $this->addSql('DROP TABLE allergens');
        $this->addSql('DROP TABLE menus_dishes');
        $this->addSql('DROP TABLE orders');
    }
}
