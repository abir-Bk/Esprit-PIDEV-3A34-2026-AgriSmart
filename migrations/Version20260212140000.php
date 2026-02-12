<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260212140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add produit.banned for admin moderation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit ADD banned TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit DROP banned');
    }
}
