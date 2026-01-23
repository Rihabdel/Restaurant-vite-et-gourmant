<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123202522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_msg (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(100) NOT NULL, message LONGTEXT NOT NULL, email VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_95B03E97A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dish_allergen (id INT AUTO_INCREMENT NOT NULL, dish_id INT DEFAULT NULL, allergen_id INT DEFAULT NULL, INDEX IDX_3C4389A5148EB0CB (dish_id), INDEX IDX_3C4389A56E775A4A (allergen_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE contact_msg ADD CONSTRAINT FK_95B03E97A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE dish_allergen ADD CONSTRAINT FK_3C4389A5148EB0CB FOREIGN KEY (dish_id) REFERENCES dishes (id)');
        $this->addSql('ALTER TABLE dish_allergen ADD CONSTRAINT FK_3C4389A56E775A4A FOREIGN KEY (allergen_id) REFERENCES allergens (id)');
        $this->addSql('ALTER TABLE orders ADD menu_id INT DEFAULT NULL, ADD user_id INT DEFAULT NULL, ADD menus_id INT DEFAULT NULL, ADD review_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEECCD7E912 FOREIGN KEY (menu_id) REFERENCES menus (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE14041B84 FOREIGN KEY (menus_id) REFERENCES menus (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE3E2E969B FOREIGN KEY (review_id) REFERENCES reviews (id)');
        $this->addSql('CREATE INDEX IDX_E52FFDEECCD7E912 ON orders (menu_id)');
        $this->addSql('CREATE INDEX IDX_E52FFDEEA76ED395 ON orders (user_id)');
        $this->addSql('CREATE INDEX IDX_E52FFDEE14041B84 ON orders (menus_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDEE3E2E969B ON orders (review_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_msg DROP FOREIGN KEY FK_95B03E97A76ED395');
        $this->addSql('ALTER TABLE dish_allergen DROP FOREIGN KEY FK_3C4389A5148EB0CB');
        $this->addSql('ALTER TABLE dish_allergen DROP FOREIGN KEY FK_3C4389A56E775A4A');
        $this->addSql('DROP TABLE contact_msg');
        $this->addSql('DROP TABLE dish_allergen');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEECCD7E912');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA76ED395');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE14041B84');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE3E2E969B');
        $this->addSql('DROP INDEX IDX_E52FFDEECCD7E912 ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEEA76ED395 ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEE14041B84 ON orders');
        $this->addSql('DROP INDEX UNIQ_E52FFDEE3E2E969B ON orders');
        $this->addSql('ALTER TABLE orders DROP menu_id, DROP user_id, DROP menus_id, DROP review_id');
    }
}
