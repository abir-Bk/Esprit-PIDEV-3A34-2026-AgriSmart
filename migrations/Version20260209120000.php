<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create users table early so commande.client_id can reference it.
 */
final class Version20260209120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table (required before commande FK)';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('users')) {
            $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, role VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, document_file VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, google_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
    }
}
