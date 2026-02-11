<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209200639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menus ADD order_befor INT NOT NULL, CHANGE description description_menu LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY `FK_877C5E79148EB0CB`');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY `FK_877C5E79CCD7E912`');
        $this->addSql('ALTER TABLE menus_dishes CHANGE dish_id dish_id INT NOT NULL');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT FK_877C5E79148EB0CB FOREIGN KEY (dish_id) REFERENCES dishes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT FK_877C5E79CCD7E912 FOREIGN KEY (menu_id) REFERENCES menus (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders CHANGE total_price total_price DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE reviews ADD commande_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F82EA2E54 FOREIGN KEY (commande_id) REFERENCES orders (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6970EB0F82EA2E54 ON reviews (commande_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menus DROP order_befor, CHANGE description_menu description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY FK_877C5E79CCD7E912');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY FK_877C5E79148EB0CB');
        $this->addSql('ALTER TABLE menus_dishes CHANGE dish_id dish_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT `FK_877C5E79CCD7E912` FOREIGN KEY (menu_id) REFERENCES menus (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT `FK_877C5E79148EB0CB` FOREIGN KEY (dish_id) REFERENCES dishes (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE orders CHANGE total_price total_price DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F82EA2E54');
        $this->addSql('DROP INDEX UNIQ_6970EB0F82EA2E54 ON reviews');
        $this->addSql('ALTER TABLE reviews DROP commande_id');
    }
}
