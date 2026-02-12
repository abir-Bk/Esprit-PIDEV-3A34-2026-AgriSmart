<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207174616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit ADD location_start DATE DEFAULT NULL, ADD location_end DATE DEFAULT NULL, DROP disponible_du, DROP disponible_au, CHANGE type type VARCHAR(255) NOT NULL, CHANGE emplacement location_address VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit ADD disponible_du DATETIME DEFAULT NULL, ADD disponible_au DATETIME DEFAULT NULL, DROP location_start, DROP location_end, CHANGE type type VARCHAR(20) NOT NULL, CHANGE location_address emplacement VARCHAR(255) DEFAULT NULL');
    }
}
