<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129082749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE menus CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY `menus_dishes_ibfk_1`');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY `menus_dishes_ibfk_2`');
        $this->addSql('ALTER TABLE menus_dishes ADD menu_id INT DEFAULT NULL, ADD dish_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT FK_877C5E79CCD7E912 FOREIGN KEY (menu_id) REFERENCES menus (id)');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT FK_877C5E79148EB0CB FOREIGN KEY (dish_id) REFERENCES dishes (id)');
        $this->addSql('CREATE INDEX IDX_877C5E79CCD7E912 ON menus_dishes (menu_id)');
        $this->addSql('CREATE INDEX IDX_877C5E79148EB0CB ON menus_dishes (dish_id)');
        $this->addSql('ALTER TABLE orders DROP INDEX FK_E52FFDEE3E2E969B, ADD UNIQUE INDEX UNIQ_E52FFDEE3E2E969B (review_id)');
        $this->addSql('DROP INDEX FK_E52FFDEE14041B84 ON orders');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY `FK_E52FFDEEA76ED395`');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY `FK_E52FFDEECCD7E912`');
        $this->addSql('ALTER TABLE orders DROP menus_id');
        $this->addSql('DROP INDEX fk_e52ffdeeccd7e912 ON orders');
        $this->addSql('CREATE INDEX IDX_E52FFDEECCD7E912 ON orders (menu_id)');
        $this->addSql('DROP INDEX fk_e52ffdeea76ed395 ON orders');
        $this->addSql('CREATE INDEX IDX_E52FFDEEA76ED395 ON orders (user_id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT `FK_E52FFDEEA76ED395` FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT `FK_E52FFDEECCD7E912` FOREIGN KEY (menu_id) REFERENCES menus (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE menus CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY FK_877C5E79CCD7E912');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY FK_877C5E79148EB0CB');
        $this->addSql('DROP INDEX IDX_877C5E79CCD7E912 ON menus_dishes');
        $this->addSql('DROP INDEX IDX_877C5E79148EB0CB ON menus_dishes');
        $this->addSql('ALTER TABLE menus_dishes DROP menu_id, DROP dish_id');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT `menus_dishes_ibfk_1` FOREIGN KEY (id) REFERENCES dishes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT `menus_dishes_ibfk_2` FOREIGN KEY (id) REFERENCES menus (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders DROP INDEX UNIQ_E52FFDEE3E2E969B, ADD INDEX FK_E52FFDEE3E2E969B (review_id)');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEECCD7E912');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA76ED395');
        $this->addSql('ALTER TABLE orders ADD menus_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX FK_E52FFDEE14041B84 ON orders (menus_id)');
        $this->addSql('DROP INDEX idx_e52ffdeea76ed395 ON orders');
        $this->addSql('CREATE INDEX FK_E52FFDEEA76ED395 ON orders (user_id)');
        $this->addSql('DROP INDEX idx_e52ffdeeccd7e912 ON orders');
        $this->addSql('CREATE INDEX FK_E52FFDEECCD7E912 ON orders (menu_id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEECCD7E912 FOREIGN KEY (menu_id) REFERENCES menus (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
