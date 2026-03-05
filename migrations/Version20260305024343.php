<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305024343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande CHANGE montant_total montant_total NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY `FK_747724FD82EA2E54`');
        $this->addSql('ALTER TABLE commande_item CHANGE prix_unitaire prix_unitaire NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FD82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consommation DROP FOREIGN KEY `FK_F993F0A2B108249D`');
        $this->addSql('ALTER TABLE consommation DROP FOREIGN KEY `FK_F993F0A2FC6CD52A`');
        $this->addSql('ALTER TABLE consommation CHANGE ressource_id ressource_id INT NOT NULL, CHANGE culture_id culture_id INT NOT NULL');
        $this->addSql('ALTER TABLE consommation ADD CONSTRAINT FK_F993F0A2B108249D FOREIGN KEY (culture_id) REFERENCES culture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consommation ADD CONSTRAINT FK_F993F0A2FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY `FK_2694D7A54CC8505A`');
        $this->addSql('ALTER TABLE demande CHANGE offre_id offre_id INT NOT NULL');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A54CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE location_reservation CHANGE unit_price unit_price NUMERIC(10, 2) NOT NULL, CHANGE total_price total_price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE offre CHANGE title title VARCHAR(255) DEFAULT NULL, CHANGE type_poste type_poste VARCHAR(255) DEFAULT NULL, CHANGE type_contrat type_contrat VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE lieu lieu VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE date_debut date_debut DATETIME DEFAULT NULL, CHANGE date_fin date_fin DATETIME DEFAULT NULL, CHANGE salaire salaire DOUBLE PRECISION DEFAULT NULL, CHANGE is_active is_active TINYINT DEFAULT NULL');
        $this->addSql('ALTER TABLE produit CHANGE prix prix NUMERIC(10, 2) NOT NULL, CHANGE promotion_price promotion_price NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE suivi_tache DROP FOREIGN KEY `FK_308B00567D026145`');
        $this->addSql('DROP INDEX IDX_308B00567D026145 ON suivi_tache');
        $this->addSql('ALTER TABLE suivi_tache CHANGE id_tache tache_id INT NOT NULL');
        $this->addSql('ALTER TABLE suivi_tache ADD CONSTRAINT FK_308B0056D2235D39 FOREIGN KEY (tache_id) REFERENCES task (id_task) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_308B0056D2235D39 ON suivi_tache (tache_id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB254433ED66 FOREIGN KEY (parcelle_id) REFERENCES parcelle (id)');
        $this->addSql('CREATE INDEX IDX_527EDB254433ED66 ON task (parcelle_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande CHANGE montant_total montant_total DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FD82EA2E54');
        $this->addSql('ALTER TABLE commande_item CHANGE prix_unitaire prix_unitaire DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT `FK_747724FD82EA2E54` FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE consommation DROP FOREIGN KEY FK_F993F0A2FC6CD52A');
        $this->addSql('ALTER TABLE consommation DROP FOREIGN KEY FK_F993F0A2B108249D');
        $this->addSql('ALTER TABLE consommation CHANGE ressource_id ressource_id INT DEFAULT NULL, CHANGE culture_id culture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE consommation ADD CONSTRAINT `FK_F993F0A2FC6CD52A` FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('ALTER TABLE consommation ADD CONSTRAINT `FK_F993F0A2B108249D` FOREIGN KEY (culture_id) REFERENCES culture (id)');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A54CC8505A');
        $this->addSql('ALTER TABLE demande CHANGE offre_id offre_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT `FK_2694D7A54CC8505A` FOREIGN KEY (offre_id) REFERENCES offre (id)');
        $this->addSql('ALTER TABLE location_reservation CHANGE unit_price unit_price DOUBLE PRECISION NOT NULL, CHANGE total_price total_price DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE offre CHANGE title title VARCHAR(255) NOT NULL, CHANGE type_poste type_poste VARCHAR(255) NOT NULL, CHANGE type_contrat type_contrat VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(255) NOT NULL, CHANGE lieu lieu VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE date_debut date_debut DATETIME NOT NULL, CHANGE date_fin date_fin DATETIME NOT NULL, CHANGE salaire salaire DOUBLE PRECISION NOT NULL, CHANGE is_active is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE produit CHANGE prix prix DOUBLE PRECISION NOT NULL, CHANGE promotion_price promotion_price DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE suivi_tache DROP FOREIGN KEY FK_308B0056D2235D39');
        $this->addSql('DROP INDEX IDX_308B0056D2235D39 ON suivi_tache');
        $this->addSql('ALTER TABLE suivi_tache CHANGE tache_id id_tache INT NOT NULL');
        $this->addSql('ALTER TABLE suivi_tache ADD CONSTRAINT `FK_308B00567D026145` FOREIGN KEY (id_tache) REFERENCES task (id_task) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_308B00567D026145 ON suivi_tache (id_tache)');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB254433ED66');
        $this->addSql('DROP INDEX IDX_527EDB254433ED66 ON task');
    }
}
