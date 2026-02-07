<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207125652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, date_commande DATETIME NOT NULL, quantite INT NOT NULL, montant_total DOUBLE PRECISION NOT NULL, mode_paiement VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, adresse_livraison VARCHAR(255) NOT NULL, temp_user_id INT NOT NULL, produit_id INT DEFAULT NULL, INDEX IDX_6EEAA67DF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(255) NOT NULL, prix DOUBLE PRECISION NOT NULL, categorie VARCHAR(255) NOT NULL, quantite_stock INT NOT NULL, image VARCHAR(255) DEFAULT NULL, is_promotion TINYINT NOT NULL, promotion_price DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DF347EFB');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE produit');
    }
}
