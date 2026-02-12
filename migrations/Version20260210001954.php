<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210001954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('produit');
        if (!$table->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE produit ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD vendeur_id INT NOT NULL, CHANGE location_address location_address VARCHAR(255) NOT NULL');
            $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27858C065E FOREIGN KEY (vendeur_id) REFERENCES users (id)');
            $this->addSql('CREATE INDEX IDX_29A5EC27858C065E ON produit (vendeur_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27858C065E');
        $this->addSql('DROP INDEX IDX_29A5EC27858C065E ON produit');
        $this->addSql('ALTER TABLE produit DROP created_at, DROP updated_at, DROP vendeur_id, CHANGE location_address location_address VARCHAR(255) DEFAULT NULL');
    }
}
