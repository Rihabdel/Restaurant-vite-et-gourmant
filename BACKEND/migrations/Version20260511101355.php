<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260511101355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE allergens CHANGE description description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE menus CHANGE picture picture TINYTEXT DEFAULT NULL, CHANGE price price NUMERIC(10, 2) NOT NULL, CHANGE is_available is_available TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE orders CHANGE delivery_cost delivery_cost DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE allergens CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE menus CHANGE picture picture LONGTEXT DEFAULT NULL, CHANGE price price FLOAT NOT NULL, CHANGE is_available is_available TINYINT DEFAULT 0');
        $this->addSql('ALTER TABLE orders CHANGE delivery_cost delivery_cost FLOAT DEFAULT NULL');
    }
}
