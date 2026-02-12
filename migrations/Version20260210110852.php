<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210110852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('commande_item')) {
            $this->addSql('CREATE TABLE commande_item (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, prix_unitaire DOUBLE PRECISION NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_747724FD82EA2E54 (commande_id), INDEX IDX_747724FDF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
            $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FD82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
            $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FDF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        }
        $commandeTable = $schema->getTable('commande');
        if (!$commandeTable->hasColumn('client_id')) {
            $this->addSql('ALTER TABLE commande DROP FOREIGN KEY `FK_6EEAA67DF347EFB`');
            $this->addSql('DROP INDEX IDX_6EEAA67DF347EFB ON commande');
            $this->addSql('ALTER TABLE commande ADD payment_ref VARCHAR(120) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD client_id INT NOT NULL, DROP quantite, DROP temp_user_id, DROP produit_id, CHANGE mode_paiement mode_paiement VARCHAR(30) NOT NULL, CHANGE statut statut VARCHAR(30) NOT NULL, CHANGE date_commande created_at DATETIME NOT NULL');
            $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D19EB6921 FOREIGN KEY (client_id) REFERENCES users (id)');
            $this->addSql('CREATE INDEX IDX_6EEAA67D19EB6921 ON commande (client_id)');
        }
        $produitTable = $schema->getTable('produit');
        if ($produitTable->hasColumn('vendeur_id')) {
            $this->addSql('ALTER TABLE produit CHANGE description description LONGTEXT NOT NULL, CHANGE type type VARCHAR(20) NOT NULL, CHANGE categorie categorie VARCHAR(50) NOT NULL, CHANGE location_address location_address VARCHAR(255) DEFAULT NULL, CHANGE vendeur_id vendeur_id INT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FD82EA2E54');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FDF347EFB');
        $this->addSql('DROP TABLE commande_item');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D19EB6921');
        $this->addSql('DROP INDEX IDX_6EEAA67D19EB6921 ON commande');
        $this->addSql('ALTER TABLE commande ADD temp_user_id INT NOT NULL, ADD produit_id INT DEFAULT NULL, DROP payment_ref, DROP updated_at, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE mode_paiement mode_paiement VARCHAR(255) NOT NULL, CHANGE created_at date_commande DATETIME NOT NULL, CHANGE client_id quantite INT NOT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT `FK_6EEAA67DF347EFB` FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DF347EFB ON commande (produit_id)');
        $this->addSql('ALTER TABLE produit CHANGE description description LONGTEXT DEFAULT NULL, CHANGE type type VARCHAR(255) NOT NULL, CHANGE categorie categorie VARCHAR(255) NOT NULL, CHANGE location_address location_address VARCHAR(255) NOT NULL, CHANGE vendeur_id vendeur_id INT NOT NULL');
    }
}
