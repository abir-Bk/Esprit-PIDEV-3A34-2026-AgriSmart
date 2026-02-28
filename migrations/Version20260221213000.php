<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create location_reservation table for persisted product rental reservations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE location_reservation (id INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, locataire_id INT NOT NULL, start_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', end_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', days INT NOT NULL, unit_price DOUBLE PRECISION NOT NULL, total_price DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_6A3180E1F347EFB (produit_id), INDEX IDX_6A3180E1CC5668EA (locataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE location_reservation ADD CONSTRAINT FK_6A3180E1F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE location_reservation ADD CONSTRAINT FK_6A3180E1CC5668EA FOREIGN KEY (locataire_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location_reservation DROP FOREIGN KEY FK_6A3180E1F347EFB');
        $this->addSql('ALTER TABLE location_reservation DROP FOREIGN KEY FK_6A3180E1CC5668EA');
        $this->addSql('DROP TABLE location_reservation');
    }
}
