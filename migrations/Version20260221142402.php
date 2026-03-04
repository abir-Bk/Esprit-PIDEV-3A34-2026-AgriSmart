<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221142402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE suivi_tache (id_suivi INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, rendement VARCHAR(255) NOT NULL, problemes LONGTEXT NOT NULL, solution LONGTEXT NOT NULL, id_tache INT NOT NULL, INDEX IDX_308B00567D026145 (id_tache), PRIMARY KEY (id_suivi)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE suivi_tache ADD CONSTRAINT FK_308B00567D026145 FOREIGN KEY (id_tache) REFERENCES task (id_task)');
        $this->addSql('ALTER TABLE offre CHANGE statut_validation statut_validation VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE suivi_tache DROP FOREIGN KEY FK_308B00567D026145');
        $this->addSql('DROP TABLE suivi_tache');
        $this->addSql('ALTER TABLE offre CHANGE statut_validation statut_validation VARCHAR(20) DEFAULT \'en_attente\' NOT NULL');
    }
}
