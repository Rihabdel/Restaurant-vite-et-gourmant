<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123192939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY `FK_877C5E7980E89383`');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY `FK_877C5E79EEE8BD30`');
        $this->addSql('DROP INDEX IDX_877C5E7980E89383 ON menus_dishes');
        $this->addSql('DROP INDEX IDX_877C5E79EEE8BD30 ON menus_dishes');
        $this->addSql('ALTER TABLE menus_dishes ADD menu_id INT DEFAULT NULL, ADD dish_id INT DEFAULT NULL, DROP menu_id_id, DROP dishes_id_id');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT FK_877C5E79CCD7E912 FOREIGN KEY (menu_id) REFERENCES menus (id)');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT FK_877C5E79148EB0CB FOREIGN KEY (dish_id) REFERENCES dishes (id)');
        $this->addSql('CREATE INDEX IDX_877C5E79CCD7E912 ON menus_dishes (menu_id)');
        $this->addSql('CREATE INDEX IDX_877C5E79148EB0CB ON menus_dishes (dish_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY FK_877C5E79CCD7E912');
        $this->addSql('ALTER TABLE menus_dishes DROP FOREIGN KEY FK_877C5E79148EB0CB');
        $this->addSql('DROP INDEX IDX_877C5E79CCD7E912 ON menus_dishes');
        $this->addSql('DROP INDEX IDX_877C5E79148EB0CB ON menus_dishes');
        $this->addSql('ALTER TABLE menus_dishes ADD menu_id_id INT DEFAULT NULL, ADD dishes_id_id INT DEFAULT NULL, DROP menu_id, DROP dish_id');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT `FK_877C5E7980E89383` FOREIGN KEY (dishes_id_id) REFERENCES dishes (id)');
        $this->addSql('ALTER TABLE menus_dishes ADD CONSTRAINT `FK_877C5E79EEE8BD30` FOREIGN KEY (menu_id_id) REFERENCES menus (id)');
        $this->addSql('CREATE INDEX IDX_877C5E7980E89383 ON menus_dishes (dishes_id_id)');
        $this->addSql('CREATE INDEX IDX_877C5E79EEE8BD30 ON menus_dishes (menu_id_id)');
    }
}
