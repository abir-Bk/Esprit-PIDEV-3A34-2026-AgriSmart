<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour les entités Task et TaskAssignment.
 */
final class Version20260209000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task and task_assignment tables for agricultural task management';
    }

    public function up(Schema $schema): void
    {
        // Table task
        $this->addSql('CREATE TABLE task (
            id_task INT AUTO_INCREMENT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            date_debut DATETIME NOT NULL,
            date_fin DATETIME DEFAULT NULL,
            priorite VARCHAR(20) NOT NULL,
            statut VARCHAR(20) NOT NULL,
            type VARCHAR(50) NOT NULL,
            localisation VARCHAR(255) DEFAULT NULL,
            parcelle_id INT DEFAULT NULL,
            culture_id INT DEFAULT NULL,
            created_by INT DEFAULT NULL,
            PRIMARY KEY(id_task)
        ) DEFAULT CHARACTER SET utf8mb4');

        // Table task_assignment
        $this->addSql('CREATE TABLE task_assignment (
            id_assignment INT AUTO_INCREMENT NOT NULL,
            task_id INT NOT NULL,
            worker_id INT NOT NULL,
            date_assignment DATETIME NOT NULL,
            statut VARCHAR(20) NOT NULL,
            INDEX IDX_TASK_ASSIGNMENT_TASK_ID (task_id),
            PRIMARY KEY(id_assignment)
        ) DEFAULT CHARACTER SET utf8mb4');

        // Clé étrangère vers task.id_task
        $this->addSql('ALTER TABLE task_assignment ADD CONSTRAINT FK_TASK_ASSIGNMENT_TASK_ID FOREIGN KEY (task_id) REFERENCES task (id_task) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task_assignment DROP FOREIGN KEY FK_TASK_ASSIGNMENT_TASK_ID');
        $this->addSql('DROP TABLE task_assignment');
        $this->addSql('DROP TABLE task');
    }
}

