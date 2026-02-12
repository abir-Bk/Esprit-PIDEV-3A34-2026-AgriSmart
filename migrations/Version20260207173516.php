<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207173516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit ADD disponible_du DATETIME DEFAULT NULL, ADD disponible_au DATETIME DEFAULT NULL, ADD emplacement VARCHAR(255) DEFAULT NULL, CHANGE type type VARCHAR(20) NOT NULL, CHANGE is_promotion is_promotion TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit DROP disponible_du, DROP disponible_au, DROP emplacement, CHANGE type type VARCHAR(255) NOT NULL, CHANGE is_promotion is_promotion TINYINT NOT NULL');
    }
}
