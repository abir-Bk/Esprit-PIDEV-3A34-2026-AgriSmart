<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221151110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task_assignment ADD CONSTRAINT FK_2CD60F156B20BA36 FOREIGN KEY (worker_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_2CD60F156B20BA36 ON task_assignment (worker_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task_assignment DROP FOREIGN KEY FK_2CD60F156B20BA36');
        $this->addSql('DROP INDEX IDX_2CD60F156B20BA36 ON task_assignment');
    }
}
