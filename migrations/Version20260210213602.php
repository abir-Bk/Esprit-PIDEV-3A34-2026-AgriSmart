<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210213602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE consommation (id INT AUTO_INCREMENT NOT NULL, quantite DOUBLE PRECISION NOT NULL, date_consommation DATETIME NOT NULL, ressource_id INT DEFAULT NULL, culture_id INT DEFAULT NULL, INDEX IDX_F993F0A2FC6CD52A (ressource_id), INDEX IDX_F993F0A2B108249D (culture_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE culture (id INT AUTO_INCREMENT NOT NULL, type_culture VARCHAR(255) NOT NULL, variete VARCHAR(255) NOT NULL, date_plantation DATE NOT NULL, date_recolte_prevue DATE NOT NULL, statut VARCHAR(255) NOT NULL, parcelle_id INT NOT NULL, INDEX IDX_B6A99CEB4433ED66 (parcelle_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE parcelle (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, surface DOUBLE PRECISION NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, type_sol VARCHAR(255) NOT NULL, user_id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ressource (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, stock_restan DOUBLE PRECISION NOT NULL, unite VARCHAR(255) NOT NULL, agriculteur_id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE consommation ADD CONSTRAINT FK_F993F0A2FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('ALTER TABLE consommation ADD CONSTRAINT FK_F993F0A2B108249D FOREIGN KEY (culture_id) REFERENCES culture (id)');
        $this->addSql('ALTER TABLE culture ADD CONSTRAINT FK_B6A99CEB4433ED66 FOREIGN KEY (parcelle_id) REFERENCES parcelle (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consommation DROP FOREIGN KEY FK_F993F0A2FC6CD52A');
        $this->addSql('ALTER TABLE consommation DROP FOREIGN KEY FK_F993F0A2B108249D');
        $this->addSql('ALTER TABLE culture DROP FOREIGN KEY FK_B6A99CEB4433ED66');
        $this->addSql('DROP TABLE consommation');
        $this->addSql('DROP TABLE culture');
        $this->addSql('DROP TABLE parcelle');
        $this->addSql('DROP TABLE ressource');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
