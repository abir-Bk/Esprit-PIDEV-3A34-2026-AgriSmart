<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260212120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add statut_validation to offre for admin approval workflow.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE offre ADD statut_validation VARCHAR(20) NOT NULL DEFAULT \'en_attente\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE offre DROP statut_validation');
    }
}
