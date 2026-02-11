<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211101007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ressource DROP FOREIGN KEY `FK_939F45447EBB810E`');
        $this->addSql('DROP INDEX IDX_939F45447EBB810E ON ressource');
        $this->addSql('ALTER TABLE ressource DROP type, CHANGE unite unite VARCHAR(50) NOT NULL, CHANGE agriculteur_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE ressource ADD CONSTRAINT FK_939F4544A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_939F4544A76ED395 ON ressource (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ressource DROP FOREIGN KEY FK_939F4544A76ED395');
        $this->addSql('DROP INDEX IDX_939F4544A76ED395 ON ressource');
        $this->addSql('ALTER TABLE ressource ADD type VARCHAR(255) NOT NULL, CHANGE unite unite VARCHAR(255) NOT NULL, CHANGE user_id agriculteur_id INT NOT NULL');
        $this->addSql('ALTER TABLE ressource ADD CONSTRAINT `FK_939F45447EBB810E` FOREIGN KEY (agriculteur_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_939F45447EBB810E ON ressource (agriculteur_id)');
    }
}
