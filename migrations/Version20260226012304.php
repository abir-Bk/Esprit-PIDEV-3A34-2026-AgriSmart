<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260226012304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('demande')) {
            return;
        }

        if ($schema->getTable('demande')->hasColumn('score')) {
            return;
        }

        $this->addSql('ALTER TABLE demande ADD score INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('demande')) {
            return;
        }

        if (!$schema->getTable('demande')->hasColumn('score')) {
            return;
        }

        $this->addSql('ALTER TABLE demande DROP score');
    }
}
