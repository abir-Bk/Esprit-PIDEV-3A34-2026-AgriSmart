<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212054914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(30) NOT NULL, mode_paiement VARCHAR(30) NOT NULL, adresse_livraison VARCHAR(255) NOT NULL, montant_total DOUBLE PRECISION NOT NULL, payment_ref VARCHAR(120) DEFAULT NULL, paid_at DATETIME DEFAULT NULL, email_sent_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, client_id INT NOT NULL, INDEX IDX_6EEAA67D19EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande_item (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, prix_unitaire DOUBLE PRECISION NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_747724FD82EA2E54 (commande_id), INDEX IDX_747724FDF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE consommation (id INT AUTO_INCREMENT NOT NULL, quantite DOUBLE PRECISION NOT NULL, date_consommation DATETIME NOT NULL, ressource_id INT DEFAULT NULL, culture_id INT DEFAULT NULL, INDEX IDX_F993F0A2FC6CD52A (ressource_id), INDEX IDX_F993F0A2B108249D (culture_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE culture (id INT AUTO_INCREMENT NOT NULL, type_culture VARCHAR(255) NOT NULL, variete VARCHAR(255) NOT NULL, date_plantation DATE NOT NULL, date_recolte_prevue DATE NOT NULL, statut VARCHAR(255) NOT NULL, parcelle_id INT NOT NULL, INDEX IDX_B6A99CEB4433ED66 (parcelle_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE demande (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, phone_number VARCHAR(255) NOT NULL, date_postulation DATETIME NOT NULL, date_modification DATETIME NOT NULL, cv VARCHAR(255) NOT NULL, lettre_motivation VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, users_id INT DEFAULT NULL, offre_id INT DEFAULT NULL, INDEX IDX_2694D7A567B3B43D (users_id), INDEX IDX_2694D7A54CC8505A (offre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offre (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, type_poste VARCHAR(255) NOT NULL, type_contrat VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, lieu VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, salaire DOUBLE PRECISION NOT NULL, is_active TINYINT NOT NULL, statut_validation VARCHAR(20) NOT NULL, agriculteur_id INT NOT NULL, INDEX IDX_AF86866F7EBB810E (agriculteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE parcelle (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, surface DOUBLE PRECISION NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, type_sol VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX IDX_C56E2CF6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(20) NOT NULL, prix DOUBLE PRECISION NOT NULL, categorie VARCHAR(50) NOT NULL, quantite_stock INT NOT NULL, image VARCHAR(255) DEFAULT NULL, is_promotion TINYINT DEFAULT 0 NOT NULL, promotion_price DOUBLE PRECISION DEFAULT NULL, location_address VARCHAR(255) DEFAULT NULL, location_start DATE DEFAULT NULL, location_end DATE DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, banned TINYINT DEFAULT 0 NOT NULL, vendeur_id INT DEFAULT NULL, INDEX IDX_29A5EC27858C065E (vendeur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ressource (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, stock_restan DOUBLE PRECISION NOT NULL, unite VARCHAR(50) NOT NULL, user_id INT NOT NULL, INDEX IDX_939F4544A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task (id_task INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, priorite VARCHAR(20) NOT NULL, statut VARCHAR(20) NOT NULL, type VARCHAR(50) NOT NULL, localisation VARCHAR(255) DEFAULT NULL, parcelle_id INT DEFAULT NULL, created_by INT DEFAULT NULL, culture_id INT DEFAULT NULL, INDEX IDX_527EDB25B108249D (culture_id), PRIMARY KEY (id_task)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task_assignment (id_assignment INT AUTO_INCREMENT NOT NULL, worker_id INT NOT NULL, date_assignment DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, task_id INT NOT NULL, INDEX IDX_2CD60F158DB60186 (task_id), PRIMARY KEY (id_assignment)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, role VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, document_file VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, google_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D19EB6921 FOREIGN KEY (client_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FD82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FDF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE consommation ADD CONSTRAINT FK_F993F0A2FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('ALTER TABLE consommation ADD CONSTRAINT FK_F993F0A2B108249D FOREIGN KEY (culture_id) REFERENCES culture (id)');
        $this->addSql('ALTER TABLE culture ADD CONSTRAINT FK_B6A99CEB4433ED66 FOREIGN KEY (parcelle_id) REFERENCES parcelle (id)');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A567B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A54CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866F7EBB810E FOREIGN KEY (agriculteur_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE parcelle ADD CONSTRAINT FK_C56E2CF6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27858C065E FOREIGN KEY (vendeur_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ressource ADD CONSTRAINT FK_939F4544A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25B108249D FOREIGN KEY (culture_id) REFERENCES culture (id)');
        $this->addSql('ALTER TABLE task_assignment ADD CONSTRAINT FK_2CD60F158DB60186 FOREIGN KEY (task_id) REFERENCES task (id_task) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D19EB6921');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FD82EA2E54');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FDF347EFB');
        $this->addSql('ALTER TABLE consommation DROP FOREIGN KEY FK_F993F0A2FC6CD52A');
        $this->addSql('ALTER TABLE consommation DROP FOREIGN KEY FK_F993F0A2B108249D');
        $this->addSql('ALTER TABLE culture DROP FOREIGN KEY FK_B6A99CEB4433ED66');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A567B3B43D');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A54CC8505A');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866F7EBB810E');
        $this->addSql('ALTER TABLE parcelle DROP FOREIGN KEY FK_C56E2CF6A76ED395');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27858C065E');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE ressource DROP FOREIGN KEY FK_939F4544A76ED395');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25B108249D');
        $this->addSql('ALTER TABLE task_assignment DROP FOREIGN KEY FK_2CD60F158DB60186');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_item');
        $this->addSql('DROP TABLE consommation');
        $this->addSql('DROP TABLE culture');
        $this->addSql('DROP TABLE demande');
        $this->addSql('DROP TABLE offre');
        $this->addSql('DROP TABLE parcelle');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE ressource');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE task_assignment');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
