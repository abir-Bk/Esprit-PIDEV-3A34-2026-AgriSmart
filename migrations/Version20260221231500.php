<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221231500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow marketplace_message.content to be nullable for voice-only messages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE marketplace_message MODIFY content LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE marketplace_message MODIFY content LONGTEXT NOT NULL');
    }
}
