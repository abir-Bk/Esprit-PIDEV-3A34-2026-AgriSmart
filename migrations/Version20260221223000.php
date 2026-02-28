<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional audio_path to marketplace_message for voice messages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE marketplace_message ADD audio_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE marketplace_message DROP audio_path');
    }
}
